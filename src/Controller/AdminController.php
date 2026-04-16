<?php

namespace App\Controller;

use App\Form\AccountType;
use App\Form\SettingsType;
use App\Service\AppSettingsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin')]
class AdminController extends AbstractController
{
    #[Route('', name: 'app_admin', methods: ['GET'])]
    public function index(): Response
    {
        return $this->redirectToRoute('app_board');
    }

    #[Route('/settings', name: 'app_admin_settings', methods: ['GET', 'POST'])]
    public function settings(
        Request $request,
        AppSettingsService $settingsService,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
    ): Response {
        $settings = $settingsService->getSettings();
        $user = $settingsService->getUser();

        $settingsForm = $this->createForm(SettingsType::class, $settings);
        $settingsForm->handleRequest($request);

        $accountForm = $this->createForm(AccountType::class, $user, [
            'action' => $this->generateUrl('app_admin_settings'),
        ]);
        $accountForm->handleRequest($request);

        if ($settingsForm->isSubmitted() && $settingsForm->isValid()) {
            $entityManager->flush();
            $settingsService->touchBoard();
            $this->addFlash('success', 'Settings updated.');

            return $this->redirectToRoute('app_admin_settings');
        }

        if ($accountForm->isSubmitted() && $accountForm->isValid()) {
            $plainPassword = (string) $accountForm->get('plainPassword')->getData();
            if ($plainPassword !== '') {
                if (mb_strlen($plainPassword) < 4) {
                    $this->addFlash('error', 'Password must be at least 4 characters.');

                    return $this->redirectToRoute('app_admin_settings');
                }

                $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));
            }

            $entityManager->flush();
            $this->addFlash('success', 'Login updated.');

            return $this->redirectToRoute('app_admin_settings');
        }

        return $this->render('admin/settings.html.twig', [
            'settings' => $settings,
            'settingsForm' => $settingsForm,
            'accountForm' => $accountForm,
        ]);
    }
}
