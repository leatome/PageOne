<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Annotations as OA;

class RegistrationController extends AbstractController
{
    /**
     * @OA\Post(
     *     path="/register",
     *     summary="Inscription d'un nouvel utilisateur",
     *     tags={"User"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/x-www-form-urlencoded",
     *             @OA\Schema(
     *                 required={"email", "plainPassword"},
     *                 @OA\Property(property="email", type="string", format="email", description="Adresse email de l'utilisateur"),
     *                 @OA\Property(property="plainPassword", type="string", format="password", description="Mot de passe en clair")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=302,
     *         description="Redirection vers la page de login après inscription réussie"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Formulaire invalide"
     *     )
     * )
     */
    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();

            // encode the plain password
            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));

            $user->setRoles(['ROLE_USER']);

            $entityManager->persist($user);
            $entityManager->flush();

            // do anything else you need here, like send an email

            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }
}
