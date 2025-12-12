<?php

namespace App\Controller;

use App\Entity\Syllabus;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/syllabuses', name: 'api_syllabuses_')]
class SyllabusController extends AbstractController
{
    #[Route('', name: 'create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $em,
        #[CurrentUser] ?User $user
    ): JsonResponse {
        // Vérifier que l'utilisateur est connecté
        if (!$user) {
            return $this->json([
                'error' => 'Unauthorized'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Récupère les données JSON
        $data = json_decode($request->getContent(), true);

        // Validation
        if (empty($data['title']) || empty($data['rawText'])) {
            return $this->json([
                'error' => 'Title and rawText are required'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Crée un nouveau Syllabus
        $syllabus = new Syllabus();
        $syllabus->setTitle($data['title']);
        $syllabus->setRawText($data['rawText']);
        $syllabus->setOwner($user);
        $syllabus->setCreatedAt(new \DateTimeImmutable());

        // Sauvegarde en base
        $em->persist($syllabus);
        $em->flush();

        // Retourne la réponse
        return $this->json([
            'id' => $syllabus->getId(),
            'title' => $syllabus->getTitle(),
            'owner' => $syllabus->getOwner()->getEmail(),
            'createdAt' => $syllabus->getCreatedAt()->format('Y-m-d H:i:s')
        ], Response::HTTP_CREATED);
    }
}