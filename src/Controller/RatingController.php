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
use OpenApi\Annotations as OA;

final class RatingController extends AbstractController
{
    /**
     * @OA\Post(
     *     path="/rate/{id}",
     *     summary="Noter un livre",
     *     tags={"Rating"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID du livre à noter",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         description="Valeur de la note entre 0.5 et 5",
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/x-www-form-urlencoded",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="rating",
     *                     type="number",
     *                     format="float",
     *                     minimum=0.5,
     *                     maximum=5,
     *                     description="Valeur de la note"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Note enregistrée et moyenne mise à jour",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="average", type="number", format="float")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Accès refusé si non authentifié"
     *     )
     * )
     */
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
