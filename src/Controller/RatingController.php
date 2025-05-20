<?php

namespace App\Controller;

use App\Entity\Book;
use App\Entity\Rating;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class RatingController extends AbstractController
{
    #[Route('/rate/{id}', name: 'rate_book', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function rate(Book $book, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        $value = (float)$request->request->get('rating');

        $existingRating = $em->getRepository(Rating::class)->findOneBy([
            'book' => $book,
            'user' => $user,
        ]);

        if (!$existingRating) {
            $existingRating = new Rating();
            $existingRating->setBook($book);
            $existingRating->setUser($user);
        }

        $existingRating->setRating($value);
        $em->persist($existingRating);
        $em->flush();

        return new JsonResponse(['success' => true, 'average' => $book->getAverageRating()]);
    }
}
