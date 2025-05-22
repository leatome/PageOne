<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use OpenApi\Annotations as OA;

final class LoginController extends AbstractController
{
    /**
     * @OA\Post(
     *     path="/login",
     *     summary="Page de connexion utilisateur",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         description="Données de connexion",
     *         required=false,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="username", type="string"),
     *             @OA\Property(property="password", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Affiche le formulaire de connexion et les erreurs éventuelles",
     *         @OA\MediaType(mediaType="text/html")
     *     )
     * )
     */
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('login/login.html.twig', [
            'last_username' => $lastUsername,
            'error'         => $error,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/logout",
     *     summary="Déconnexion de l'utilisateur",
     *     tags={"Auth"},
     *     @OA\Response(
     *         response=204,
     *         description="Déconnexion réussie, pas de contenu retourné"
     *     )
     * )
     */
    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
