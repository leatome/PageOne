<?php

namespace App\Controller;

use App\Entity\Book;
use App\Entity\Rating;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\SecurityBundle\Security;
use App\Repository\RatingRepository;
use App\Entity\UserBookCollection;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/book')]
final class BookController extends AbstractController
{
    #[Route('/{id}', name: 'book_show', methods: ['GET'])]
    #[OA\Get(
        summary: "Afficher un livre",
        description: "Renvoie les détails d'un livre spécifique par son ID",
        tags: ['Book']
    )]
    #[OA\Response(
        response: 200,
        description: "Détails du livre",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "id", type: "integer"),
                new OA\Property(property: "title", type: "string"),
                new OA\Property(property: "author", type: "string"),
                new OA\Property(property: "description", type: "string", nullable: true),
            ]
        )
    )]
    public function show(Book $book): Response
    {
        return $this->render('book/show.html.twig', [
            'book' => $book,
        ]);
    }

    #[Route('/{id}/rate', name: 'book_rate', methods: ['POST'])]
    #[OA\Post(
        summary: "Noter un livre",
        description: "Permet à un utilisateur connecté de noter un livre entre 0.5 et 5 étoiles",
        tags: ['Book']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            type: "object",
            required: ["rating"],
            properties: [
                new OA\Property(property: "rating", type: "number", format: "float", example: 4.5)
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: "Note enregistrée",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "success", type: "boolean", example: true),
                new OA\Property(property: "average", type: "float", example: 4.3)
            ]
        )
    )]
    #[OA\Response(response: 403, description: "Utilisateur non autorisé")]
    #[OA\Response(response: 400, description: "Note invalide")]
    public function rate(Request $request, Book $book, EntityManagerInterface $em, Security $security, RatingRepository $ratingRepo): JsonResponse {
        $user = $security->getUser();
        if (!$user || !$this->isGranted('ROLE_USER')) {
            return new JsonResponse(['success' => false], 403);
        }

        $data = json_decode($request->getContent(), true);
        $note = floatval($data['rating'] ?? 0);
        if ($note < 0.5 || $note > 5) {
            return new JsonResponse(['success' => false], 400);
        }

        // Vérifie si une note existe déjà
        $rating = $ratingRepo->findOneBy(['book' => $book, 'user' => $user]) ?? new Rating();
        $rating->setBook($book);
        $rating->setUser($user);
        $rating->setRating($note);
        $em->persist($rating);
        $em->flush();

        // Calcule la nouvelle moyenne
        $ratings = $book->getRatings()->map(fn($r) => $r->getRating())->toArray();
        $average = round(array_sum($ratings) / count($ratings), 2);

        return new JsonResponse(['success' => true, 'average' => $average]);
    }

    #[Route('/{id}/toggle-favorite', name: 'book_toggle_favorite', methods: ['POST'])]
    #[OA\Post(
        summary: "Ajouter ou retirer un livre des favoris",
        description: "Bascule l'état de favori d'un livre pour l'utilisateur actuel",
        tags: ['Book']
    )]
    #[OA\Response(
        response: 200,
        description: "Résultat de l'action",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "success", type: "boolean"),
                new OA\Property(property: "action", type: "string", example: "added")
            ]
        )
    )]
    public function toggleFavorite(Book $book, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $existing = $em->getRepository(UserBookCollection::class)->findOneBy([
            'userCollection' => $user,
            'book' => $book,
        ]);

        if ($existing) {
            $em->remove($existing);
            $message = 'removed';
        } else {
            $favorite = new UserBookCollection();
            $favorite->setUserCollection($user)->setBook($book);
            $em->persist($favorite);
            $message = 'added';
        }

        $em->flush();

        return new JsonResponse(['success' => true, 'action' => $message]);
    }

    #[Route('/{id}/read', name: 'book_read', methods: ['GET'])]
    #[OA\Get(
        summary: "Lire le contenu d’un livre",
        description: "Renvoie le texte brut du livre si disponible",
        tags: ['Book']
    )]
    #[OA\Response(
        response: 200,
        description: "Texte du livre",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "text", type: "string")
            ]
        )
    )]
    public function read(Book $book, HttpClientInterface $httpClient): Response
    {
        $textUrl = $book->getTextUrl();

        if (!$textUrl) {
            throw $this->createNotFoundException("Ce livre ne contient pas de texte lisible.");
        }

        try {
            $response = $httpClient->request('GET', $textUrl);
            $content = $response->getContent();

            // Nettoyage du contenu Gutendex
            $startMarker = '*** START OF THIS PROJECT GUTENBERG EBOOK';
            $endMarker = '*** END OF THIS PROJECT GUTENBERG EBOOK';

            $start = strpos($content, $startMarker);
            $end = strpos($content, $endMarker);

            if ($start !== false && $end !== false) {
                // Premier saut de ligne après le marqueur START
                $start = strpos($content, "\n", $start);
                $content = substr($content, $start, $end - $start);
            }

        } catch (\Exception $e) {
            return new Response("Erreur lors de la récupération du texte.");
        }

        return $this->render('book/read.html.twig', [
            'book' => $book,
            'text' => $content,
        ]);
    }
}
