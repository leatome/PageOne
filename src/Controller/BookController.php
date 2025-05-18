<?php

namespace App\Controller;

use App\Entity\Book;
use App\Entity\Rating;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\SecurityBundle\Security;
use App\Repository\RatingRepository;

final class BookController extends AbstractController
{
    #[Route('/book/{id}', name: 'book_show')]
    public function show(Book $book, RatingRepository $ratingRepo): Response
    {
        $averageRating = $ratingRepo->getAverageForBook($book);
        $userRating = null;
        if ($this->isGranted('ROLE_USER')) {
            $userRating = $ratingRepo->findOneBy(['book' => $book, 'user' => $this->getUser()])?->getRating();
        }

        return $this->render('book/show.html.twig', [
            'book' => $book,
            'averageRating' => $averageRating,
            'userRating' => $userRating,
        ]);
    }

    #[Route('/book/{id}/rate', name: 'book_rate', methods: ['POST'])]
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
}
