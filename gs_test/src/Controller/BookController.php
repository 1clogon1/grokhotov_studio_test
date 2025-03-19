<?php

namespace App\Controller;

use App\DTO\BookData;
use App\Entity\Book;
use App\Repository\BookRepository;
use App\Service\CacheService;
use App\Service\ValidateService;
use App\Utils\CacheKeys;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class BookController extends AbstractController
{
    private BookRepository $bookRepository;
    private ValidatorInterface $validator;
    private ValidateService $validateService;
    private EntityManagerInterface $entityManager;
    private CacheInterface $cache;
    private CacheService $cacheService;

    public function __construct(
        BookRepository $bookRepository,
        ValidatorInterface $validator,
        ValidateService $validateService,
        EntityManagerInterface $entityManager,
        CacheInterface $cache,
        CacheService $cacheService
    ) {
        $this->bookRepository = $bookRepository;
        $this->validator = $validator;
        $this->validateService = $validateService;
        $this->entityManager = $entityManager;
        $this->cache = $cache;
        $this->cacheService = $cacheService;
    }


    #[Route('/api/book', name: 'api_books_get', methods: ['GET'])]
    public function getBooks(Request $request): JsonResponse
    {
        $categoryId = $request->query->get('category');

        $data = $this->cache->get(CacheKeys::BOOK_KEY . '_' . $categoryId, function (ItemInterface $item) use ($categoryId) {
            $item->expiresAfter(3600);

            $books = $this->bookRepository->findAll();
            if ($categoryId) {
                $books = $this->bookRepository->findByCategory($categoryId);
            }

            return array_map(fn($book) => [
                'id' => $book->getId(),
                'title' => $book->getTitle(),
                'isbn' => $book->getIsbn(),
                'page_count' => $book->getPageCount(),
                'published_date' => $book->getPublishedDate()->format('Y-m-d H:i:s'),
                'thumbnail_url' => $book->getThumbnailUrl(),
                'short_description' => $book->getShortDescription(),
                'long_description' => $book->getLongDescription(),
                'status' => $book->getStatus(),
            ], $books);
        });

        return new JsonResponse(["data" => $data], 200);
    }

    #[Route('/api/book/{id}', name: 'api_book_get', methods: ['GET'])]
    public function getBook(int $id): JsonResponse
    {
        $book = $this->bookRepository->find($id);

        if (!$book) {
            return new JsonResponse(["error" => "Book not found"], 404);
        }

        $authors = array_map(fn($author) => [
            'id' => $author->getId(),
            'name' => $author->getName(),
        ], $book->getAuthors()->toArray());

        $categories = array_map(fn($category) => [
            'id' => $category->getId(),
            'name' => $category->getName(),
        ], $book->getCategories()->toArray());

        $data = [
            'id' => $book->getId(),
            'title' => $book->getTitle(),
            'isbn' => $book->getIsbn(),
            'page_count' => $book->getPageCount(),
            'published_date' => $book->getPublishedDate()->format('Y-m-d H:i:s'),
            'thumbnail_url' => $book->getThumbnailUrl(),
            'short_description' => $book->getShortDescription(),
            'long_description' => $book->getLongDescription(),
            'status' => $book->getStatus(),
            'authors' => $authors,
            'categories' => $categories,
        ];

        return new JsonResponse(["data" => $data], 200);
    }

    #[Route('/api/book/{id}/related', name: 'api_book_related', methods: ['GET'])]
    public function getRelatedBooks(int $id): JsonResponse
    {
        $book = $this->bookRepository->find($id);

        if (!$book) {
            return new JsonResponse(["error" => "Book not found"], 404);
        }

        $categories = $book->getCategories();
        $relatedBooks = [];

        foreach ($categories as $category) {
            $booksInCategory = $category->getBooks()->toArray();
            foreach ($booksInCategory as $relatedBook) {
                if ($relatedBook->getId() !== $book->getId()) {
                    $relatedBooks[] = $relatedBook;
                }
            }
        }

        $relatedBooks = array_unique($relatedBooks, SORT_REGULAR);

        $relatedBooks = array_slice($relatedBooks, 0, 4);

        $data = array_map(fn($relatedBook) => [
            'id' => $relatedBook->getId(),
            'title' => $relatedBook->getTitle(),
            'thumbnail_url' => $relatedBook->getThumbnailUrl(),
            'authors' => array_map(fn($author) => [
                'id' => $author->getId(),
                'name' => $author->getName(),
            ], $relatedBook->getAuthors()->toArray()),
        ], $relatedBooks);

        return new JsonResponse(["data" => $data], 200);
    }

    #[Route('/api/admin/book', name: 'api_books_post', methods: ['POST'])]
    public function postBooks(Request $request): JsonResponse
    {
        $uploadedFile = $request->files->get('thumbnail');
        $data = $request->request->all();

        if (!$uploadedFile || !$uploadedFile->isValid()) {
            return new JsonResponse(['error' => 'Ошибка загрузки файла'], 400);
        }

        if (!in_array($uploadedFile->getMimeType(), ['image/jpeg']) ||
            !in_array($uploadedFile->guessExtension(), ['jpg', 'jpeg'])) {
            return new JsonResponse(['error' => 'Файл должен быть в формате JPG'], 400);
        }

        if ($uploadedFile->getSize() > 10 * 1024 * 1024) {
            return new JsonResponse(['error' => 'Файл слишком большой. Максимальный размер: 10 МБ'], 400);
        }

        $filename = md5(uniqid()) . '.' . $uploadedFile->guessExtension();
        $uploadedFile->move($this->getParameter('kernel.project_dir') . '/public/uploads/books/', $filename);

        $bookDTO = new BookData(
            $data['title'],
            $data['isbn'],
            $data['pageCount'],
            $data['publishedDate'],
            '/uploads/books/' . $filename,
            $data['shortDescription'],
            $data['longDescription'],
            $data['status']
        );

        $validErrors = $this->validator->validate($bookDTO, null, ['calculate-price']);
        if (count($validErrors) > 0) {
            return new JsonResponse(['error' => $this->validateService->formatValidationErrors($validErrors)], 422);
        }

        $book = new Book();
        $book->setTitle($bookDTO->title)
            ->setIsbn($bookDTO->isbn)
            ->setPageCount($bookDTO->pageCount)
            ->setPublishedDate(new \DateTime($bookDTO->publishedDate))
            ->setThumbnailUrl($bookDTO->thumbnailUrl)
            ->setShortDescription($bookDTO->shortDescription)
            ->setLongDescription($bookDTO->longDescription)
            ->setStatus($bookDTO->status);

        $this->entityManager->persist($book);
        $this->entityManager->flush();

        $this->cacheService->deleteCache(CacheKeys::BOOK_KEY);

        return new JsonResponse(["message" => "Книга успешно сохранена"], 200);
    }

    #[Route('/api/admin/book', name: 'api_books_put', methods: ['PUT'])]
    public function putBooks(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $id = $data['id'] ?? null;

        if (empty($id) || !is_numeric($id)) {
            return new JsonResponse(["message" => "Ошибка в передаче id"], 422);
        }

        $book = $this->bookRepository->find($data['id']);
        if (!$book) {
            return new JsonResponse(["message" => "Книга не найдена"], 404);
        }

        $book->setTitle($data['title']);
        $book->setIsbn($data['isbn']);
        $book->setPageCount((int)$data['pageCount']);
        $book->setPublishedDate(new \DateTime($data['publishedDate']));
        $book->setShortDescription($data['shortDescription']);
        $book->setLongDescription($data['longDescription']);
        $book->setStatus($data['status']);

        $this->entityManager->flush();

        $this->cacheService->deleteCache(CacheKeys::BOOK_KEY);

        return new JsonResponse(["message" => "Книга успешно обновлена"], 200);
    }

    #[Route('/api/admin/book', name: 'api_books_delete', methods: ['DELETE'])]
    public function deleteBooks(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $id = $data["id"] ?? null;

        if (!isset($id) || empty($id) || !is_numeric($id)) {
            return new JsonResponse(["error" => "Ошибка в передаче id книги"], 422);
        }

        $book = $this->bookRepository->findOneBy(["id" => $id]);

        if (!empty($book)) {
            $thumbnailUrl = $book->getThumbnailUrl();

            if (!empty($thumbnailUrl) && file_exists($thumbnailUrl)) {
                unlink($thumbnailUrl);
            }

            $this->entityManager->remove($book);
            $this->entityManager->flush();

            $this->cacheService->deleteCache(CacheKeys::BOOK_KEY);

            return new JsonResponse(["message" => "Книга успешно удалена"], 200);
        }

        return new JsonResponse(["message" => "Такой книги нету"], 404);
    }
}
