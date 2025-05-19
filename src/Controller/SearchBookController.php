<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Repository\BookRepository;
use Symfony\Component\HttpFoundation\Response;

final class SearchBookController extends AbstractController
{
    #[Route('/search', name: 'book_search')]
    public function search(Request $request, BookRepository $bookRepository): Response
    {
        $term = $request->query->get('q', '');
        $books = [];

        if ($term) {
            $books = $bookRepository->searchBooksByTerm($term);
        }

        return $this->render('search_book/results_search.html.twig', [
            'books' => $books,
            'term' => $term,
        ]);
    }
}
