<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Session;
use App\Entity\User;

class SessionController extends AbstractController
{
    #[Route('/api/sessions/{id}/complete', name: 'session_complete', methods: ['PATCH'])]
    public function completeSession(
        int $id,
        Request $request,
        #[CurrentUser] User $user,
        EntityManagerInterface $em
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        // Récupère la session
        $session = $em->getRepository(Session::class)->find($id);
        
        if (!$session) {
            return $this->json([
                'error' => 'Session not found'
            ], 404);
        }

        // Vérifie que l'utilisateur a accès (propriétaire du CoursePlan)
        if ($session->getCoursePlan()->getOwner() !== $user) {
            return $this->json([
                'error' => 'Access denied'
            ], 403);
        }

        // Met à jour la session
        if (isset($data['done'])) {
            $session->setDone($data['done']);
        }
        
        if (isset($data['actualNotes'])) {
            $session->setActualNotes($data['actualNotes']);
        }

        $em->flush();

        return $this->json([
            'message' => 'Session marked as complete',
            'id' => $session->getId(),
            'done' => $session->isDone(),
            'actualNotes' => $session->getActualNotes(),
            'title' => $session->getTitle(),
            'indexNumber' => $session->getIndexNumber()
        ], 200);
    }
}