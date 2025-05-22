<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\SecurityBundle\Security;
use OpenApi\Annotations as OA;

final class ProfilController extends AbstractController
{
    /**
     * @OA\Get(
     *     path="/profil",
     *     summary="Page de profil utilisateur",
     *     tags={"User"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Affiche la page profil avec la collection de livres par catégorie",
     *         @OA\MediaType(mediaType="text/html")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Accès refusé si l'utilisateur n'est pas connecté"
     *     )
     * )
     */
    #[Route('/profil', name: 'user_profile')]
    public function profile(Security $security): Response
    {
        /** @var User $user */
        $user = $security->getUser();

        if (!$user) {
            throw $this->createAccessDeniedException();
        }

        $categories = [];

        foreach ($user->getCollections() as $collection) {
            $book = $collection->getBook();
            foreach ($book->getCategories() as $category) {
                $categoryName = $category->getName();
                if (!isset($categories[$categoryName])) {
                    $categories[$categoryName] = [];
                }
                $categories[$categoryName][] = [
                    'id' => $book->getId(),
                    'title' => $book->getTitle(),
                    'coverUrl' => $book->getCoverUrl(),
                ];
            }
        }

        ksort($categories);

        return $this->render('profil/profile.html.twig', [
            'categories' => $categories,
            'user' => $user,
        ]);
    }
}
