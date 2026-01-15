<?php

namespace App\Command;

use App\Entity\User;
use App\Entity\Syllabus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:demo-data',
    description: 'Crée un utilisateur de démo avec des syllabus pré-chargés',
)]
class DemoDataCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $user = new User();
            $user->setEmail('demo@example.com');
            $hashedPassword = $this->passwordHasher->hashPassword($user, 'demo123');
            $user->setPassword($hashedPassword);
            $user->setRoles(['ROLE_TEACHER']);

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $io->success("Utilisateur créé: demo@example.com / demo123");

            $syllabi = [
                [
                    'title' => 'Développement Web avec Symfony',
                    'content' => 'Ce cours couvre les fondamentaux de Symfony 7:
- Installation et configuration de Symfony
- Architecture MVC et routing
- Contrôleurs et vues (Twig)
- Doctrine ORM et migrations
- Validation et formulaires
- API Platform pour les API REST
- Tests unitaires et fonctionnels
- Déploiement et production

Prérequis: Connaissance de PHP orienté objet

Durée estimée: 40 heures',
                ],
                [
                    'title' => 'React et JavaScript Moderne',
                    'content' => 'Maîtriser React pour le développement frontend:
- Fondamentaux de JavaScript ES6+
- JSX et composants React
- État (State) et Props
- Hooks (useState, useEffect, useContext)
- Formulaires et validation
- Fetch API et axios pour les requêtes
- Routing avec React Router
- Gestion d\'état avec Context API ou Redux
- Optimisation et performance

Durée estimée: 30 heures',
                ],
                [
                    'title' => 'Base de Données PostgreSQL',
                    'content' => 'Conception et utilisation de PostgreSQL:
- Modèle relationnel et normalisation
- Créer et gérer les tables
- Requêtes SELECT, INSERT, UPDATE, DELETE
- Joins et sous-requêtes
- Agrégation et groupement
- Transactions et intégrité des données
- Indexes et optimisation
- Sauvegardes et restauration
- Connexion avec ORMs (Doctrine, Sequelize, etc.)

Durée estimée: 25 heures',
                ],
            ];

            foreach ($syllabi as $syllabusData) {
                $syllabus = new Syllabus();
                $syllabus->setTitle($syllabusData['title']);
                $syllabus->setRawText($syllabusData['content']);
                $syllabus->setOwner($user);

                $this->entityManager->persist($syllabus);
            }

            $this->entityManager->flush();

            $io->success("3 syllabus créés avec succès !");
            $io->info([
                "Utilisateur de démo créé:",
                "Email: demo@example.com",
                "Mot de passe: demo123",
                "",
                "Syllabus disponibles:",
                "- Développement Web avec Symfony",
                "- React et JavaScript Moderne",
                "- Base de Données PostgreSQL",
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error("Erreur: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
