<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Créer un nouvel utilisateur administrateur',
)]
class CreateAdminCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private ValidatorInterface $validator
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::OPTIONAL, 'Email de l\'administrateur')
            ->addArgument('username', InputArgument::OPTIONAL, 'Nom d\'utilisateur')
            ->addOption('super-admin', null, InputOption::VALUE_NONE, 'Donner les droits super admin')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Création d\'un nouvel administrateur');

        $email = $input->getArgument('email');
        if (!$email) {
            $question = new Question('Email de l\'administrateur : ');
            $email = $io->askQuestion($question);
        }

        $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existingUser) {
            $io->error('Un utilisateur avec cet email existe déjà.');
            return Command::FAILURE;
        }

        $username = $input->getArgument('username');
        if (!$username) {
            $question = new Question('Nom d\'utilisateur : ');
            $username = $io->askQuestion($question);
        }

        $question = new Question('Mot de passe : ');
        $question->setHidden(true);
        $question->setHiddenFallback(false);
        $password = $io->askQuestion($question);

        $question = new Question('Confirmer le mot de passe : ');
        $question->setHidden(true);
        $question->setHiddenFallback(false);
        $confirmPassword = $io->askQuestion($question);

        if ($password !== $confirmPassword) {
            $io->error('Les mots de passe ne correspondent pas.');
            return Command::FAILURE;
        }

        $user = new User();
        $user->setEmail($email);
        $user->setUsername($username);

        $roles = ['ROLE_ADMIN'];
        if ($input->getOption('super-admin')) {
            $roles[] = 'ROLE_SUPER_ADMIN';
        }
        $user->setRoles($roles);

        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            $io->error('Erreurs de validation :');
            foreach ($errors as $error) {
                $io->error($error->getMessage());
            }
            return Command::FAILURE;
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success('Administrateur créé avec succès !');
        $io->table(
            ['Email', 'Nom d\'utilisateur', 'Rôles'],
            [[$user->getEmail(), $user->getUsername(), implode(', ', $user->getRoles())]]
        );

        return Command::SUCCESS;
    }
}
