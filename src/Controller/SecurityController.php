<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;

class SecurityController extends AbstractController
{
    /**
     * Route POST /api/login
     * Authentification avec email + password
     */
    #[Route('/api/login', name: 'api_login', methods: ['POST', 'OPTIONS'])]
    public function login(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em
    ): JsonResponse {
        if ($request->getMethod() === 'OPTIONS') {
            return $this->json([], Response::HTTP_OK);
        }

        $data = json_decode($request->getContent(), true);

        if (empty($data['email']) || empty($data['password'])) {
            return $this->json([
                'error' => 'Email and password are required'
            ], Response::HTTP_BAD_REQUEST);
        }

        $user = $em->getRepository(User::class)->findOneBy(['email' => $data['email']]);
        
        if (!$user) {
            return $this->json([
                'error' => 'Invalid credentials'
            ], Response::HTTP_UNAUTHORIZED);
        }

        if (!$passwordHasher->isPasswordValid($user, $data['password'])) {
            return $this->json([
                'error' => 'Invalid credentials'
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json([
            'message' => 'Login successful',
            'user' => $user->getEmail()
        ]);
    }

    /**
     * Route POST /api/register
     * Création d'un nouveau compte
     */
    #[Route('/api/register', name: 'api_register', methods: ['POST', 'OPTIONS'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em
    ): JsonResponse {
        if ($request->getMethod() === 'OPTIONS') {
            return $this->json([], Response::HTTP_OK);
        }

        $data = json_decode($request->getContent(), true);

        if (empty($data['email']) || empty($data['password'])) {
            return $this->json([
                'error' => 'Email and password are required'
            ], Response::HTTP_BAD_REQUEST);
        }

        $existingUser = $em->getRepository(User::class)->findOneBy(['email' => $data['email']]);
        if ($existingUser) {
            return $this->json([
                'error' => 'This email is already registered'
            ], Response::HTTP_CONFLICT);
        }

        $user = new User();
        $user->setEmail($data['email']);
        $user->setPassword($passwordHasher->hashPassword($user, $data['password']));
        $user->setRoles(['ROLE_TEACHER']);

        $em->persist($user);
        $em->flush();

        return $this->json([
            'message' => 'User created successfully',
            'email' => $user->getEmail()
        ], Response::HTTP_CREATED);
    }
}