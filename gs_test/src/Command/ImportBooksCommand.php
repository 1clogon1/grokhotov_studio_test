<?php

namespace App\Command;

use App\Entity\Book;
use App\Entity\Author;
use App\Entity\Category;
use App\Service\CacheService;
use App\Utils\CacheKeys;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Filesystem\Filesystem;
use Psr\Log\LoggerInterface;

class ImportBooksCommand extends Command
{
    private HttpClientInterface $httpClient;
    private EntityManagerInterface $entityManager;
    private Filesystem $filesystem;
    private string $projectDir;
    private string $bookImportUrl;
    private CacheService $cacheService;
    private LoggerInterface $logger;

    public function __construct(
        HttpClientInterface $httpClient,
        EntityManagerInterface $entityManager,
        Filesystem $filesystem,
        string $projectDir,
        string $bookImportUrl,
        CacheService $cacheService,
        LoggerInterface $logger
    ) {
        parent::__construct();
        $this->httpClient = $httpClient;
        $this->entityManager = $entityManager;
        $this->filesystem = $filesystem;
        $this->projectDir = $projectDir;
        $this->bookImportUrl = $bookImportUrl;
        $this->cacheService = $cacheService;
        $this->logger = $logger;
    }

    protected static $defaultName = 'app:import-books';

    protected function configure()
    {
        $this
            ->setDescription('Импортирует книги из JSON файла по ссылке и загружает их в базу данных, включая загрузку изображений')
            ->setHelp('Эта команда парсит книги и категории из JSON файла и сохраняет их в базу данных, добавляет изображения на сервер');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $response = $this->httpClient->request('GET', $this->bookImportUrl);
            $jsonData = $response->toArray();

            foreach ($jsonData as $bookData) {
                $book = $this->entityManager->getRepository(Book::class)->findOneBy(['isbn' => $bookData['isbn'] ?? '']);

                if (!$book) {
                    $book = new Book();
                    $book->setTitle($bookData['title'] ?? '')
                        ->setIsbn($bookData['isbn'] ?? '')
                        ->setPageCount($bookData['pageCount'] ?? 0)
                        ->setPublishedDate(new \DateTime($bookData['publishedDate']['$date'] ?? 'now'))
                        ->setShortDescription($bookData['shortDescription'] ?? '')
                        ->setLongDescription($bookData['longDescription'] ?? '')
                        ->setStatus($bookData['status'] ?? '');

                    $thumbnailUrl = $bookData['thumbnailUrl'] ?? '';
                    if ($thumbnailUrl) {
                        $headers = get_headers($thumbnailUrl, 1);
                        if (isset($headers['Content-Type']) && strpos($headers['Content-Type'], 'image/jpeg') !== false) {
                            $imagePath = $this->downloadImage($thumbnailUrl, $bookData['isbn'] ?? '');
                            if ($imagePath) {
                                $book->setThumbnailUrl($imagePath);
                            }
                        } else {
                            $this->logger->error('Ошибка: Изображение должно быть формата JPG.', ['image' => $thumbnailUrl]);
                        }
                    }

                    foreach ($bookData['authors'] ?? [] as $authorName) {
                        $author = $this->entityManager->getRepository(Author::class)->findOneBy(['name' => $authorName]) ?? new Author();
                        $author->setName($authorName);
                        $this->entityManager->persist($author);
                        $book->addAuthor($author);
                    }

                    foreach ($bookData['categories'] ?? [] as $categoryName) {
                        $category = $this->entityManager->getRepository(Category::class)->findOneBy(['name' => $categoryName]) ?? new Category();
                        $category->setName($categoryName);
                        $this->entityManager->persist($category);
                        $book->addCategory($category);
                    }

                    if (empty($bookData['categories'])) {
                        $newCategory = $this->entityManager->getRepository(Category::class)->findOneBy(['name' => 'Новинки']);
                        if (!$newCategory) {
                            $newCategory = new Category();
                            $newCategory->setName('Новинки');
                            $this->entityManager->persist($newCategory);
                        }
                        $book->addCategory($newCategory);
                    }

                    $this->entityManager->persist($book);
                }

                $this->entityManager->flush();
            }

            $output->writeln('Книги успешно импортированы в базу данных.');

            $this->cacheService->deleteCache(CacheKeys::CATEGORY_KEY);
            $this->cacheService->deleteCache(CacheKeys::BOOK_KEY);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->logger->error('Ошибка при выполнении импорта книг', ['exception' => $e]);
            $output->writeln('Произошла ошибка при импорте данных.');
            return Command::FAILURE;
        }
    }

    private function downloadImage(string $url, string $isbn): ?string
    {
        try {
            $imageContent = file_get_contents($url);
            if ($imageContent === false) {
                return null;
            }

            $directory = $this->projectDir . '/public/uploads/books/';
            if (!file_exists($directory)) {
                $this->filesystem->mkdir($directory);
            }

            $imageName = $isbn . '.jpg';
            $filePath = $directory . $imageName;

            file_put_contents($filePath, $imageContent);

            return '/uploads/books/' . $imageName;
        } catch (\Exception $e) {
            $this->logger->error('Ошибка при загрузке изображения', ['exception' => $e, 'isbn' => $isbn]);
            return null;
        }
    }
}
