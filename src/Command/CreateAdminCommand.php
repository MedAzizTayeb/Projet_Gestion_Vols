<?php

namespace App\Command;

use App\Entity\Administrateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Créer un nouvel administrateur pour AeroManager',
)]
class CreateAdminCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Création d\'un Administrateur AeroManager');

        // Demander les informations
        $helper = $this->getHelper('question');

        $matriculeQuestion = new Question('Matricule (ex: ADM001): ', 'ADM' . rand(100, 999));
        $matricule = $helper->ask($input, $output, $matriculeQuestion);

        $nomQuestion = new Question('Nom: ');
        $nomQuestion->setValidator(function ($answer) {
            if (!is_string($answer) || empty(trim($answer))) {
                throw new \RuntimeException('Le nom ne peut pas être vide');
            }
            return $answer;
        });
        $nom = $helper->ask($input, $output, $nomQuestion);

        $prenomQuestion = new Question('Prénom: ');
        $prenomQuestion->setValidator(function ($answer) {
            if (!is_string($answer) || empty(trim($answer))) {
                throw new \RuntimeException('Le prénom ne peut pas être vide');
            }
            return $answer;
        });
        $prenom = $helper->ask($input, $output, $prenomQuestion);

        $emailQuestion = new Question('Email: ');
        $emailQuestion->setValidator(function ($answer) {
            if (!filter_var($answer, FILTER_VALIDATE_EMAIL)) {
                throw new \RuntimeException('Email invalide');
            }
            return $answer;
        });
        $email = $helper->ask($input, $output, $emailQuestion);

        $passwordQuestion = new Question('Mot de passe: ');
        $passwordQuestion->setHidden(true);
        $passwordQuestion->setHiddenFallback(false);
        $passwordQuestion->setValidator(function ($answer) {
            if (strlen($answer) < 6) {
                throw new \RuntimeException('Le mot de passe doit contenir au moins 6 caractères');
            }
            return $answer;
        });
        $password = $helper->ask($input, $output, $passwordQuestion);

        $io->section('Niveau d\'accès');
        $io->listing([
            '1 - Super Administrateur (accès complet)',
            '2 - Administrateur Principal',
            '3 - Administrateur',
            '4 - Assistant Administrateur'
        ]);
        $niveauQuestion = new Question('Niveau d\'accès [1-4]: ', '3');
        $niveauQuestion->setValidator(function ($answer) {
            if (!in_array($answer, ['1', '2', '3', '4'])) {
                throw new \RuntimeException('Veuillez choisir un niveau entre 1 et 4');
            }
            return (int) $answer;
        });
        $niveau = $helper->ask($input, $output, $niveauQuestion);

        $telephoneQuestion = new Question('Téléphone (optionnel): ');
        $telephone = $helper->ask($input, $output, $telephoneQuestion);

        $departements = [
            '1' => 'Direction Générale',
            '2' => 'Opérations',
            '3' => 'Maintenance',
            '4' => 'Service Client',
            '5' => 'Ressources Humaines',
            '6' => 'Finance',
            '7' => 'IT',
            '8' => 'Sécurité'
        ];

        $io->section('Département');
        foreach ($departements as $key => $value) {
            $io->writeln("$key - $value");
        }
        $departementQuestion = new Question('Département [1-8]: ', '1');
        $departementChoice = $helper->ask($input, $output, $departementQuestion);
        $departement = $departements[$departementChoice] ?? 'Direction Générale';

        // Créer l'administrateur
        try {
            $admin = new Administrateur();
            $admin->setMatricule($matricule);
            $admin->setNom($nom);
            $admin->setPrenom($prenom);
            $admin->setEmail($email);
            $admin->setNiveauAcces($niveau);
            $admin->setDepartement($departement);

            if ($telephone) {
                $admin->setTelephone($telephone);
            }

            // Définir les rôles en fonction du niveau
            $roles = ['ROLE_ADMIN'];
            if ($niveau === 1) {
                $roles[] = 'ROLE_SUPER_ADMIN';
            }
            $admin->setRoles($roles);

            // Hash du mot de passe
            $hashedPassword = $this->passwordHasher->hashPassword($admin, $password);
            $admin->setPassword($hashedPassword);

            // Persister
            $this->entityManager->persist($admin);
            $this->entityManager->flush();

            $io->success('Administrateur créé avec succès!');

            $io->table(
                ['Champ', 'Valeur'],
                [
                    ['Matricule', $matricule],
                    ['Nom complet', "$prenom $nom"],
                    ['Email', $email],
                    ['Niveau d\'accès', $niveau],
                    ['Département', $departement],
                    ['Téléphone', $telephone ?: 'Non renseigné'],
                    ['Rôles', implode(', ', $roles)],
                ]
            );

            $io->note([
                'Vous pouvez maintenant vous connecter avec:',
                "Email: $email",
                "Mot de passe: (celui que vous avez saisi)",
                '',
                'URL de connexion: /login'
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('Erreur lors de la création de l\'administrateur: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
