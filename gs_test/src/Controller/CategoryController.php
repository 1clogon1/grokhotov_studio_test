<?php

namespace App\Controller;

use App\DTO\CategoryData;
use App\Entity\Category;
use App\Repository\CategoryRepository;
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

class CategoryController extends AbstractController
{
    private CategoryRepository $categoryRepository;
    private ValidatorInterface $validator;
    private ValidateService $validateService;
    private EntityManagerInterface $entityManager;
    private CacheInterface $cache;
    private CacheService $cacheService;

    public function __construct(
        CategoryRepository $categoryRepository,
        ValidatorInterface $validator,
        ValidateService $validateService,
        EntityManagerInterface $entityManager,
        CacheInterface $cache,
        CacheService $cacheService
    ) {
        $this->categoryRepository = $categoryRepository;
        $this->validator = $validator;
        $this->validateService = $validateService;
        $this->entityManager = $entityManager;
        $this->cache = $cache;
        $this->cacheService = $cacheService;
    }

    #[Route('/api/category', name: 'api_category_get', methods: ['GET'])]
    public function getCategory(): JsonResponse
    {
        $data = $this->cache->get(CacheKeys::CATEGORY_KEY, function (ItemInterface $item) {
            $item->expiresAfter(3600);

            $categories = $this->categoryRepository->findAll();
            return array_map(fn($category) => [
                'id' => $category->getId(),
                'name' => $category->getName(),
            ], $categories);
        });

        return new JsonResponse(["data" => $data], 200);
    }

    #[Route('/api/admin/category', name: 'api_category_post', methods: ['POST'])]
    public function postCategory(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $categoryDTO = new CategoryData($data['name'] ?? null);
        $validErrors = $this->validator->validate($categoryDTO);

        if (count($validErrors) > 0) {
            return new JsonResponse(['error' => $this->validateService->formatValidationErrors($validErrors)], 422);
        }

        $existingCategory = $this->categoryRepository->findOneBy(["name" => $categoryDTO->name]);
        if ($existingCategory) {
            return new JsonResponse(["message" => "Категория с таким именем уже существует."], 422);
        }

        $category = new Category();
        $category->setName($categoryDTO->name);

        $this->entityManager->persist($category);
        $this->entityManager->flush();

        $this->cacheService->deleteCache(CacheKeys::CATEGORY_KEY);

        return new JsonResponse(["message" => "Категория успешно сохранена"], 200);
    }

    #[Route('/api/admin/category', name: 'api_category_put', methods: ['PUT'])]
    public function putCategory(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['id']) || empty($data['name'])) {
            return new JsonResponse(["message" => "Не переданы обязательные параметры: id и name"], 422);
        }

        $id = $data['id'];
        $name = $data['name'];

        if (!is_numeric($id)) {
            return new JsonResponse(["message" => "Ошибка в передаче id"], 422);
        }

        $category = $this->categoryRepository->find($id);
        if ($category) {
            $category->setName($name);

            $this->entityManager->flush();

            $this->cacheService->deleteCache(CacheKeys::CATEGORY_KEY);

            return new JsonResponse(["message" => "Категория обновлена"], 200);
        }

        return new JsonResponse(["message" => "Категория не найдена"], 404);
    }

    #[Route('/api/admin/category', name: 'api_category_delete', methods: ['DELETE'])]
    public function deleteCategory(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $id = $data['id'] ?? null;

        if (empty($id) || !is_numeric($id)) {
            return new JsonResponse(["message" => "Ошибка в передаче id"], 422);
        }

        $category = $this->categoryRepository->find($id);
        if ($category) {
            $this->entityManager->remove($category);
            $this->entityManager->flush();

            $this->cacheService->deleteCache(CacheKeys::CATEGORY_KEY);

            return new JsonResponse(["message" => "Категория успешно удалена"], 200);
        }


        return new JsonResponse(["message" => "Категория не найдена"], 404);
    }
}
