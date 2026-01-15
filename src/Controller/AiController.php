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
use App\Entity\Exercise;
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

    #[Route('/api/ai/adjust-course-plan', name: 'ai_adjust_course_plan', methods: ['POST'])]
    public function adjustCoursePlan(
        Request $request,
        #[CurrentUser] User $user,
        EntityManagerInterface $em,
        MistralClient $mistralClient
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        // Validation
        if (empty($data['coursePlanId'])) {
            return $this->json([
                'error' => 'coursePlanId is required'
            ], 400);
        }

        // Récupère le plan de cours
        $coursePlan = $em->getRepository(CoursePlan::class)->find($data['coursePlanId']);
        
        if (!$coursePlan || $coursePlan->getOwner() !== $user) {
            return $this->json([
                'error' => 'Course plan not found or access denied'
            ], 404);
        }

        // Récupère la dernière session complétée
        $sessions = $coursePlan->getSessions();
        $completedSessions = $sessions->filter(fn($s) => $s->isDone())->getValues();
        $remainingSessions = $sessions->filter(fn($s) => !$s->isDone())->getValues();

        if (empty($completedSessions)) {
            return $this->json([
                'error' => 'No completed sessions yet'
            ], 400);
        }

        $lastCompleted = end($completedSessions);

        // Récupère le syllabus original
        $syllabus = $coursePlan->getSyllabus();

        // Prépare les données pour le prompt
        $remarks = $data['remarks'] ?? 'Aucune';

        // Construit le contexte pour Mistral
        $completedContent = "Séances complétées :\n";
        foreach ($completedSessions as $s) {
            $completedContent .= "- {$s->getTitle()}\n";
            if ($s->getActualNotes()) {
                $completedContent .= "  Notes : {$s->getActualNotes()}\n";
            }
        }

        $remainingContent = "Séances restantes à réajuster :\n";
        foreach ($remainingSessions as $s) {
            $remainingContent .= "- {$s->getTitle()}\n";
        }

        $prompt = <<<PROMPT
Tu es un expert en pédagogie. Tu dois réajuster un plan de cours en fonction de la progression réelle.

Syllabus du cours :
{$syllabus->getRawText()}

Plan de cours actuel : {$coursePlan->getTitle()}
Durée totale prévue : {$coursePlan->getExpectedTotalHours()}h

$completedContent

$remainingContent

Remarques supplémentaires : $remarks

Réajuste les séances restantes en fonction de ce qui a été réellement couvert.
- Adapte les objectifs et contenus
- Ajuste les durées si nécessaire
- Réorganise si besoin

Réponds AU FORMAT JSON STRICT (sans texte avant/après) avec TOUTES les séances réajustées :

[
  {
    "indexNumber": 2,
    "title": "Titre réajusté",
    "objectives": ["objectif 1"],
    "contents": ["contenu ajusté"],
    "activities": ["activité"],
    "resources": ["ressource"],
    "plannedDurationMinutes": 120
  }
]

Retourne un array JSON avec CHAQUE séance restante réajustée (dans l'ordre).
PROMPT;

        try {
            // Appelle Mistral
            $result = $mistralClient->generateJson($prompt);

            // Mistral retourne un array de sessions réajustées
            if (!is_array($result)) {
                throw new \Exception('Invalid response format from Mistral');
            }

            // Applique les ajustements aux sessions restantes
            $adjustmentIndex = 0;
            foreach ($remainingSessions as $session) {
                if (isset($result[$adjustmentIndex])) {
                    $adjustment = $result[$adjustmentIndex];
                    
                    if (isset($adjustment['title'])) {
                        $session->setTitle($adjustment['title']);
                    }
                    if (isset($adjustment['objectives'])) {
                        $session->setObjectives($adjustment['objectives']);
                    }
                    if (isset($adjustment['contents'])) {
                        $session->setContents($adjustment['contents']);
                    }
                    if (isset($adjustment['activities'])) {
                        $session->setActivities($adjustment['activities']);
                    }
                    if (isset($adjustment['resources'])) {
                        $session->setResources($adjustment['resources']);
                    }
                    if (isset($adjustment['plannedDurationMinutes'])) {
                        $session->setPlannedDurationMinutes($adjustment['plannedDurationMinutes']);
                    }

                    $em->persist($session);
                    $adjustmentIndex++;
                }
            }

            $em->flush();

            return $this->json([
                'message' => 'Course plan adjusted successfully',
                'coursePlanId' => $coursePlan->getId(),
                'sessionsAdjusted' => count($remainingSessions),
                'summary' => 'Séances réajustées selon la progression réelle'
            ], 200);

        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Failed to adjust course plan: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/api/ai/update-course-plan', name: 'ai_update_course_plan', methods: ['POST'])]
    public function updateCoursePlan(
        Request $request,
        #[CurrentUser] User $user,
        EntityManagerInterface $em,
        MistralClient $mistralClient
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        // Validation
        if (empty($data['coursePlanId']) || empty($data['sessionId'])) {
            return $this->json([
                'error' => 'coursePlanId and sessionId are required'
            ], 400);
        }

        // Récupère le CoursePlan
        $coursePlan = $em->getRepository(CoursePlan::class)->find($data['coursePlanId']);
        if (!$coursePlan || $coursePlan->getOwner() !== $user) {
            return $this->json([
                'error' => 'Course plan not found or access denied'
            ], 404);
        }

        // Récupère la session complétée
        $completedSession = $em->getRepository(Session::class)->find($data['sessionId']);
        if (!$completedSession || $completedSession->getCoursePlan() !== $coursePlan) {
            return $this->json([
                'error' => 'Session not found or access denied'
            ], 404);
        }

        // Récupère les sessions suivantes
        $allSessions = $coursePlan->getSessions();
        $nextSessions = $allSessions->filter(
            fn($s) => $s->getIndexNumber() > $completedSession->getIndexNumber()
        )->getValues();

        if (empty($nextSessions)) {
            return $this->json([
                'message' => 'No more sessions to adapt',
                'adaptedSessions' => []
            ]);
        }

        // Prépare les données pour l'IA
        $prompt = <<<PROMPT
Tu es un expert en pédagogie. Tu dois adapter les séances suivantes en fonction de ce qui a été réellement couvert.

Syllabus du cours :
{$coursePlan->getSyllabus()->getRawText()}

Séance complétée :
- Titre : {$completedSession->getTitle()}
- Ce qui a été réellement fait : {$completedSession->getActualNotes()}

Séances suivantes à adapter :
PROMPT;

        foreach ($nextSessions as $session) {
            $prompt .= "\n- {$session->getIndexNumber()}. {$session->getTitle()}";
        }

        $prompt .= <<<PROMPT

Adapte les séances suivantes en fonction de ce qui a été réellement couvert.
- Ajuste les objectifs si nécessaire
- Modifie les contenus pour éviter les redondances
- Propose des activités mieux adaptées au rythme réel

Réponds AU FORMAT JSON STRICT (sans texte avant/après) :

{
  "gap_summary": "Résumé de l'écart entre prévu et réalisé",
  "proposed_changes_for_next_sessions": [
    "Idée d'ajustement global",
    "Autre idée..."
  ],
  "updated_next_session": {
    "index": 3,
    "objectives": ["objectif adapté"],
    "contents": ["contenu ajusté"],
    "activities": ["activité adaptée"]
  }
}
PROMPT;

        try {
            $result = $mistralClient->generateJson($prompt);

            // Mets à jour la première séance suivante avec les données adaptées
            if (!empty($nextSessions) && isset($result['updated_next_session'])) {
                $nextSession = $nextSessions[0];
                
                if (isset($result['updated_next_session']['objectives'])) {
                    $nextSession->setObjectives($result['updated_next_session']['objectives']);
                }
                if (isset($result['updated_next_session']['contents'])) {
                    $nextSession->setContents($result['updated_next_session']['contents']);
                }
                if (isset($result['updated_next_session']['activities'])) {
                    $nextSession->setActivities($result['updated_next_session']['activities']);
                }
                
                $em->persist($nextSession);
                $em->flush();
            }

            return $this->json([
                'message' => 'Next sessions adapted successfully',
                'adaptations' => [
                    'gap_summary' => $result['gap_summary'] ?? '',
                    'proposed_changes' => $result['proposed_changes_for_next_sessions'] ?? [],
                    'updated_session_id' => $nextSessions[0]->getId() ?? null
                ]
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Failed to adapt sessions: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/api/ai/generate-exercises', name: 'ai_generate_exercises', methods: ['POST'])]
    public function generateExercises(
        Request $request,
        #[CurrentUser] User $user,
        EntityManagerInterface $em,
        MistralClient $mistralClient
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        // Validation
        if (empty($data['sessionId'])) {
            return $this->json([
                'error' => 'sessionId is required'
            ], 400);
        }

        $difficulty = $data['difficulty'] ?? 'MEDIUM';
        if (!in_array($difficulty, ['EASY', 'MEDIUM', 'HARD'])) {
            return $this->json([
                'error' => 'difficulty must be EASY, MEDIUM, or HARD'
            ], 400);
        }

        // Récupère le nombre d'exercices à générer
        $count = isset($data['count']) ? (int)$data['count'] : 3;
        if ($count < 1 || $count > 10) {
            $count = 3; // Valeur par défaut si hors limites
        }

        // Récupère la session
        $session = $em->getRepository(Session::class)->find($data['sessionId']);
        
        if (!$session || $session->getCoursePlan()->getOwner() !== $user) {
            return $this->json([
                'error' => 'Session not found or access denied'
            ], 404);
        }

        // Prépare les données pour le prompt
        $coursePlan = $session->getCoursePlan();
        $objectives = is_array($session->getObjectives()) ? implode(', ', $session->getObjectives()) : $session->getObjectives();
        $contents = is_array($session->getContents()) ? implode(', ', $session->getContents()) : $session->getContents();

        $prompt = <<<PROMPT
Tu es un expert en pédagogie. Tu dois générer des exercices pour une séance de formation.

Titre de la séance : {$session->getTitle()}
Objectifs : $objectives
Contenus : $contents
Niveau de difficulté : $difficulty

Génère exactement $count exercice(s) variés et pertinents pour cette séance.

Réponds AU FORMAT JSON STRICT (sans texte avant/après) :

[
  {
    "title": "Titre de l'exercice",
    "instruction": "Énoncé détaillé et clair de l'exercice",
    "difficulty": "EASY|MEDIUM|HARD",
    "expectedDurationMinutes": 30,
    "correction": {
      "steps": ["Étape 1", "Étape 2"],
      "tips": ["Conseil 1", "Conseil 2"]
    }
  }
]

Chaque exercice doit :
- Avoir un titre clair et descriptif
- Une instruction complète et compréhensible
- Un niveau de difficulté cohérent avec celui demandé
- Une durée estimée réaliste
- Une correction avec étapes et conseils
- Générer EXACTEMENT $count exercice(s), ni plus, ni moins
PROMPT;

        try {
            // Appelle Mistral
            $result = $mistralClient->generateJson($prompt);

            // ✅ Vérifie le format
            if (is_object($result)) {
                $result = (array) $result;  // Convertit l'objet en array
            }

            if (!is_array($result)) {
                throw new \Exception('Invalid response format from Mistral: ' . gettype($result));
            }

            // Crée les exercices
            $exercisesCreated = 0;
            foreach ($result as $exerciseData) {
                // ✅ Vérification supplémentaire
                if (!is_array($exerciseData)) {
                    continue;  // Saute les entrées invalides
                }

                $exercise = new Exercise();
                $exercise->setTitle($exerciseData['title'] ?? 'Sans titre');
                $exercise->setInstruction($exerciseData['instruction'] ?? '');
                $exercise->setDifficulty($exerciseData['difficulty'] ?? $difficulty);
                $exercise->setExpectedDurationMinutes($exerciseData['expectedDurationMinutes'] ?? 30);
                
                // Stocke la correction en JSON
                $correction = [
                    'steps' => $exerciseData['correction']['steps'] ?? [],
                    'tips' => $exerciseData['correction']['tips'] ?? []
                ];
                $exercise->setCorrection($correction);
                
                $exercise->setSession($session);
                
                $em->persist($exercise);
                $exercisesCreated++;
            }

            $em->flush();

            return $this->json([
                'message' => 'Exercises generated successfully',
                'sessionId' => $session->getId(),
                'exercisesCreated' => $exercisesCreated,
                'difficulty' => $difficulty
            ], 201);

        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Failed to generate exercises: ' . $e->getMessage()
            ], 500);
        }
    }
}