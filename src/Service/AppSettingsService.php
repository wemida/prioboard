<?php

namespace App\Service;

use App\Entity\AppSettings;
use App\Entity\User;
use App\Repository\AppSettingsRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppSettingsService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AppSettingsRepository $settingsRepository,
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function getSettings(): AppSettings
    {
        $settings = $this->settingsRepository->find(1);

        if ($settings instanceof AppSettings) {
            return $settings;
        }

        $settings = new AppSettings();
        $settings->setApiKey(bin2hex(random_bytes(16)));
        $this->entityManager->persist($settings);
        $this->entityManager->flush();

        return $settings;
    }

    public function getUser(): User
    {
        $user = $this->userRepository->find(1);

        if ($user instanceof User) {
            return $user;
        }

        $user = new User();
        $user->setUsername('admin');
        $user->setPassword($this->passwordHasher->hashPassword($user, 'admin'));
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    public function touchBoard(): void
    {
        $settings = $this->getSettings();
        $settings->bumpBoardVersion();
        $this->entityManager->flush();
    }
}
