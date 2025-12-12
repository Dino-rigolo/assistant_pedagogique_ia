<?php

namespace App\Controller; // Contrôleur de sécurité pour la gestion de l'authentification

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController; // Étend AbstractController pour bénéficier des fonctionnalités de base des contrôleurs Symfony
use Symfony\Component\HttpFoundation\JsonResponse;// Réponse JSON pour les API
use Symfony\Component\HttpFoundation\Request; // Requête HTTP entrante
use Symfony\Component\HttpFoundation\Response;// Constantes de codes de statut HTTP
use Symfony\Component\Routing\Attribute\Route;// Annotation de route
use Symfony\Component\Security\Http\Attribute\CurrentUser;// Injection de l'utilisateur courant
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;  // Service de hachage de mot de passe
use Doctrine\ORM\EntityManagerInterface;  // Gestionnaire d'entités Doctrine
use App\Entity\User;// Entité User

class SecurityController extends AbstractController
{
    /**
     * Route POST /api/login
     * Endpoint/porte d'accès pour se connecter et recevoir un JWT(JSON Web Token)
     */
    #[Route('/api/login', name: 'api_login', methods: ['POST', 'OPTIONS'])]
    public function login(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em
    ): JsonResponse {
        // Gère les requêtes OPTIONS pour CORS
        if ($request->getMethod() === 'OPTIONS') {
            return $this->json([], Response::HTTP_OK);
        }
        // Récupère les credentials du JSON
        $data = json_decode($request->getContent(), true);

        if (empty($data['email']) || empty($data['password'])) {
            return $this->json([
                'error' => 'Email and password are required'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Charge l'utilisateur depuis la DB
        $user = $em->getRepository(User::class)->findOneBy(['email' => $data['email']]);
        
        // Vérifie si l'utilisateur existe
        if (!$user) {
            return $this->json([
                'error' => 'Invalid credentials'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Vérifie le password
        if (!$passwordHasher->isPasswordValid($user, $data['password'])) {
            return $this->json([
                'error' => 'Invalid credentials'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // ✅ Login réussi
        return $this->json([
            'message' => 'Login successful',
            'user' => $user->getEmail()
        ]);
    }
    /**
     * Route POST /api/register
     * Endpoint pour créer un nouveau compte
     */
    #[Route('/api/register', name: 'api_register', methods: ['POST', 'OPTIONS'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em
    ): JsonResponse {
        // Gère les requêtes OPTIONS pour CORS
        if ($request->getMethod() === 'OPTIONS') {
            return $this->json([], Response::HTTP_OK);
        }
        //Récupère les données JSON de la requête
        $data = json_decode($request->getContent(), true);

        //Validation des données
        if (empty($data['email']) || empty($data['password'])) {
            return $this->json([
                'error' => 'Email and password are required'
            ], Response::HTTP_BAD_REQUEST);
        }

        //Vérifier si l'email existe déjà
        $existingUser = $em->getRepository(User::class)->findOneBy(['email' => $data['email']]);
        if ($existingUser) {
            return $this->json([
                'error' => 'This email is already registered'
            ], Response::HTTP_CONFLICT);
        }

        //Crée un nouvel utilisateur
        $user = new User();
        $user->setEmail($data['email']);
        
        // Hash le password
        $hashedPassword = $passwordHasher->hashPassword($user, $data['password']);
        $user->setPassword($hashedPassword);
        
        // Set le rôle par défaut
        $user->setRoles(['ROLE_TEACHER']);

        //Sauvegarde en base de données
        $em->persist($user);
        $em->flush();

        //Retourne une réponse de succès
        return $this->json([
            'message' => 'User created successfully',
            'email' => $user->getEmail()
        ], Response::HTTP_CREATED);
    }
}