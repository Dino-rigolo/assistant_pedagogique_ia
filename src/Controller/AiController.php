<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use App\Entity\Syllabus;
use App\Entity\CoursePlan;
use App\Entity\Session;
use App\Service\MistralClient;

class AiController extends AbstractController
{
    #[Route('/api/ai/generate-course-plan', name: 'ai_generate_course_plan', methods: ['POST'])]
    public function generateCoursePlan(
        Request $request,
        #[CurrentUser] User $user,
        EntityManagerInterface $em,
        MistralClient $mistralClient
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        // Validation des données
        if (empty($data['syllabusId']) || empty($data['nbSessions'])) {
            return $this->json([
                'error' => 'syllabusId and nbSessions are required'
            ], 400);
        }

        // Récupère le syllabus
        $syllabus = $em->getRepository(Syllabus::class)->find($data['syllabusId']);
        
        if (!$syllabus || $syllabus->getOwner() !== $user) {
            return $this->json([
                'error' => 'Syllabus not found or access denied'
            ], 404);
        }

        // Prompt pour générer le plan
        $prompt = <<<PROMPT
Tu es un expert en pédagogie. 

Voici le syllabus d'un cours :
{$syllabus->getRawText()}

Je veux créer un plan de cours avec {$data['nbSessions']} séances.

Réponds AU FORMAT JSON STRICT (sans texte avant/après) :

{
  "title": "Titre du plan",
  "generalPlan": "Vue globale du cours",
  "evaluationCriteria": {
    "exam": "40%",
    "project": "60%"
  },
  "expectedTotalHours": 30,
  "sessions": [
    {
      "indexNumber": 1,
      "title": "Titre de la séance",
      "objectives": ["objectif 1", "objectif 2"],
      "contents": ["contenu 1", "contenu 2"],
      "activities": ["activité 1"],
      "resources": ["ressource 1"],
      "plannedDurationMinutes": 120
    }
  ]
}
PROMPT;

        try {
            // Appelle Mistral
            $result = $mistralClient->generateJson($prompt);

            // Crée le CoursePlan
            $coursePlan = new CoursePlan();
            $coursePlan->setTitle($result['title'] ?? 'Plan de cours');
            $coursePlan->setGeneralPlan($result['generalPlan'] ?? '');
            $coursePlan->setEvaluationCriteria($result['evaluationCriteria'] ?? []);
            $coursePlan->setExpectedTotalHours($result['expectedTotalHours'] ?? 30);
            $coursePlan->setNbSessionsPlanned(count($result['sessions'] ?? []));
            $coursePlan->setCreatedAt(new \DateTimeImmutable());
            $coursePlan->setSyllabus($syllabus);
            $coursePlan->setOwner($user);

            $em->persist($coursePlan);
            $em->flush();

            // Crée les sessions
            foreach ($result['sessions'] ?? [] as $sessionData) {
                $session = new Session();
                $session->setIndexNumber($sessionData['indexNumber']);
                $session->setTitle($sessionData['title']);
                $session->setObjectives($sessionData['objectives'] ?? []);
                $session->setContents($sessionData['contents'] ?? []);
                $session->setActivities($sessionData['activities'] ?? []);
                $session->setResources($sessionData['resources'] ?? []);
                $session->setPlannedDurationMinutes($sessionData['plannedDurationMinutes'] ?? 120);
                $session->setDone(false);
                $session->setCoursePlan($coursePlan);

                $em->persist($session);
            }

            $em->flush();

            return $this->json([
                'message' => 'Course plan generated successfully',
                'coursePlanId' => $coursePlan->getId(),
                'nbSessionsCreated' => count($result['sessions'] ?? [])
            ], 201);

        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Failed to generate course plan: ' . $e->getMessage()
            ], 500);
        }
    }
}