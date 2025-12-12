<?php

namespace App\Controller; // Contrôleur de sécurité pour la gestion de l'authentification

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController; // Étend AbstractController pour bénéficier des fonctionnalités de base des contrôleurs Symfony
use Symfony\Component\HttpFoundation\JsonResponse;// Réponse JSON pour les API
use Symfony\Component\HttpFoundation\Response;// Constantes de codes de statut HTTP
use Symfony\Component\Routing\Attribute\Route;// Annotation de route
use Symfony\Component\Security\Http\Attribute\CurrentUser;// Injection de l'utilisateur courant
use App\Entity\User;// Entité User

class SecurityController extends AbstractController
{
    /**
     * Route POST /api/login
     * Endpoint/porte d'accès pour se connecter et recevoir un JWT(JSON Web Token)
     */
    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(#[CurrentUser] ?User $user): JsonResponse
    {
        // Si pas d'utilisateur, credentials invalides
        if (!$user) {
            return $this->json([
                'error' => 'Invalid credentials'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // LexikJWTAuthenticationBundle génère le JWT automatiquement
        // et l'ajoute dans la réponse en header "Authorization: Bearer <token>"
        return $this->json([
            'message' => 'Login successful',
            'user' => $user->getEmail()
        ]);
    }
}