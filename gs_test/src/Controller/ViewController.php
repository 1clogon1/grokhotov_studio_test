<?php

namespace App\Controller;

use App\Entity\Category;
use App\Repository\BookRepository;
use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ViewController extends AbstractController
{
    #[Route('/admin', name: 'admin_view')]
    public function admin(): Response
    {
        return $this->render('admin/admin.html.twig');
    }

    #[Route('/', name: 'book_view')]
    public function book(CategoryRepository $categoryRepository): Response
    {
        $categories = $categoryRepository->findAll();
        return $this->render('book/book.html.twig', [
            'categories' => $categories,
        ]);
    }

    #[Route('/book/{id}', name: 'book_detail_view')]
    public function bookDetail(int $id): Response
    {
        return $this->render('book/book_detail.html.twig', ['id' => $id]);
    }

    #[Route('/admin/books', name: 'admin_books')]
    public function adminBooks(BookRepository $bookRepository): Response
    {
        $books = $bookRepository->findAll();

        return $this->render('admin/books.html.twig', [
            'books' => $books,
        ]);
    }

    #[Route('/admin/categories', name: 'admin_categories')]
    public function adminCategories(CategoryRepository $categoryRepository): Response
    {
        $categories = $categoryRepository->findAll();

        return $this->render('admin/categories.html.twig', [
            'categories' => $categories,
        ]);
    }

    #[Route('/admin/book/create', name: 'admin_book_create')]
    public function createBook(Request $request): Response
    {
        return $this->render('admin/book_form.html.twig');
    }

    #[Route('/admin/book/edit/{id}', name: 'admin_book_edit')]
    public function editBook(int $id, Request $request, BookRepository $bookRepository): Response
    {
        $book = $bookRepository->find($id);

        if (!$book) {
            throw $this->createNotFoundException('Книга не найдена');
        }

        return $this->render('admin/book_form.html.twig', [
            'book' => $book,
        ]);
    }

    #[Route('/admin/book/delete/{id}', name: 'admin_book_delete')]
    public function deleteBook(int $id): Response
    {
        return $this->redirectToRoute('admin_books');
    }

    #[Route('/admin/category/edit/{id}', name: 'admin_category_edit')]
    public function editCategory(int $id, CategoryRepository $categoryRepository): Response
    {
        $category = $categoryRepository->find($id);

        if (!$category) {
            throw $this->createNotFoundException('Категория не найдена');
        }

        return $this->render('admin/category_form.html.twig', [
            'category' => $category,
        ]);
    }

    #[Route('/admin/category/create', name: 'admin_category_create')]
    public function createCategory(): Response
    {
        return $this->render('admin/category_form.html.twig', [
            'category' => new Category(),
        ]);
    }

}
