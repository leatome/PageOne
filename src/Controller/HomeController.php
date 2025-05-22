<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Annotations as OA;

final class HomeController extends AbstractController
{
    /**
     * @OA\Get(
     *     path="/",
     *     summary="Page d'accueil affichant les catégories triées par nom",
     *     tags={"Home"},
     *     @OA\Response(
     *         response=200,
     *         description="Page d'accueil avec les catégories",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="name", type="string")
     *             )
     *         )
     *     )
     * )
     */
    #[Route('/', name: 'app_home')]
    public function index(CategoryRepository $categoryRepository): Response
    {
        $categories = $categoryRepository->createQueryBuilder('c')
            ->orderBy('c.name', 'ASC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        return $this->render('home/index.html.twig', [
            'categories' => $categories,
        ]);
    }
}
