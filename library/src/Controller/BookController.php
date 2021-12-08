<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use App\Repository\BookRepository;
use App\Repository\CategoryRepository;
use App\Entity\Book;

class BookController extends AbstractController
{
    private BookRepository $bookRepository;

    private CategoryRepository $categoryRepository;

    private SerializerInterface $serializer;

    function __construct(BookRepository $bookRepository, CategoryRepository $categoryRepository, SerializerInterface $serializer)
    {
        $this->bookRepository = $bookRepository;
        $this->categoryRepository = $categoryRepository;
        $this->serializer = $serializer;
    }

    /**
     * @Route("/books", name="get-books", methods="GET")
     */
    public function getBooks(): JsonResponse
    {
        return $this->json($this->bookRepository->findAll());
    }

    /**
     * @Route("/books/{bookId}", name="get-book", methods="GET")
     */
    public function getBook($bookId): Response
    {
        return $this->json($this->bookRepository->find($bookId));
    }

    /**
     * @Route("/book/{bookSlug}", name="get-book-by-slug", methods="GET")
     */
    public function getBookBySlug($bookSlug): Response
    {
        return $this->json($this->bookRepository->findBy(['slug' => $bookSlug]));
    }

    /**
     * @Route("/books/author/{authorId}", name="get-books-by-author", methods="GET")
     */
    public function getBooksByAuthor($authorId): Response
    {
        $books = $this->bookRepository->findBy(['author' => $authorId]);
        return $this->json($books);
    }

    /**
     * @Route("/books/category/{categorySlug}", name="get-books-by-category", methods="GET")
     */
    public function getBooksByCategory($categorySlug): Response
    {
        $categoryId = $this->categoryRepository->findBy(['slug' => \strtolower($categorySlug)]);
        $books = $this->bookRepository->findBy(['category' => $categoryId]);
        return $this->json($books);
    }

    /**
     * @Route("/categories", name="get-categories", methods="GET")
     */
    public function getCategories(): Response
    {
        $response = $this->json($this->categoryRepository->findAll());
        $response->headers->add([
                'content-type' => 'application/json',
                'Access-Control-Allow-Origin' => '*',
                'Access-Control-Allow-Methods' => 'GET, POST, OPTIONS, PUT, PATCH, DELETE',
                'Access-Control-Allow-Headers' => 'X-Requested-With,content-type'
        ]);
        return $response;
    }

    /**
     * @Route("/books", name="create-book", methods="POST")
     * @param Request $request
     */
    public function createBook(Request $request): Response
    {
        $book = new Book();
        $book->setTitle($request->request->get('title'));
        $book->setSlug($request->request->get('title'));
        $book->setIsbn($request->request->get('isbn'));
        $book->setPageCount($request->request->get('pageCount'));
        $book->setFilePath($request->request->get('filePath'));

        $this->getDoctrine()->getManager()->persist($book);
        $this->getDoctrine()->getManager()->flush();

        return $this->json($book);
    }

    /**
     * @Route("/books/{bookId}", name="edit-book", methods="POST")
     * @param Request $request
     */
    public function editBook(Request $request, string $bookId): Response
    {
        $book = $this->bookRepository->find($bookId);

        if ($request->request->has('title')) {
            $book->setTitle($request->request->get('title'));
        }
        if ($request->request->has('slug')) {
            $book->setSlug($request->request->get('slug'));
        }
        if ($request->request->has('isbn')) {
            $book->setIsbn($request->request->get('isbn'));
        }
        if ($request->request->has('filePath')) {
            $book->setFilePath($request->request->get('filePath'));
        }
        if ($request->request->has('pageCount')) {
            $book->setPageCount($request->request->get('pageCount'));
        }

        $this->getDoctrine()->getManager()->flush();

        return $this->json($book);
    }

    /**
     * @Route("/books/{bookId}", name="delete-book", methods="DELETE")
     */
    public function deleteBook($bookId): Response
    {
        $book = $this->bookRepository->find($bookId);
        $this->getDoctrine()->getManager()->remove($book);
        $this->getDoctrine()->getManager()->flush();

        return Response::create();
    }

    /**
     * @Route("/books/{bookSlug}/pdf", name="get-book-pdf", methods="GET")
     */
    public function downloadBook($bookSlug): Response
    {
        $book = $this->bookRepository->findOneBy(['slug' => $bookSlug]);
        if ($book) {
            return $this->redirect($book->getFilePath());
        }

        return $this->json(['error' => 'Book not found'], Response::HTTP_NOT_FOUND);
    }
}
