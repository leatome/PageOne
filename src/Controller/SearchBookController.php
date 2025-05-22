<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\BookRepository;
use OpenApi\Annotations as OA;

final class SearchBookController extends AbstractController
{
    /**
     * @OA\Get(
     *     path="/search",
     *     summary="Recherche de livres par terme",
     *     tags={"Book"},
     *     @OA\Parameter(
     *         name="q",
     *         in="query",
     *         description="Terme de recherche (titre, auteur, catégories...)",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Page HTML avec résultats de recherche",
     *         @OA\MediaType(
     *             mediaType="text/html"
     *         )
     *     )
     * )
     */
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
