<?php

namespace App\Controller;

use App\Entity\Card;
use App\Form\AccountType;
use App\Form\CardType;
use App\Form\SettingsType;
use App\Repository\CardRepository;
use App\Service\AppSettingsService;
use App\Service\CardService;
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
    #[Route('', name: 'app_admin', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        CardRepository $cardRepository,
        CardService $cardService,
        AppSettingsService $settingsService,
    ): Response {
        $settings = $settingsService->getSettings();
        $card = new Card();
        $form = $this->createForm(CardType::class, $card, [
            'colors_enabled' => $settings->isCardColorsEnabled(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$settings->isCardColorsEnabled()) {
                $card->setColor('neutral');
            }

            $cardService->createCard($card);
            $this->addFlash('success', 'Card created.');

            return $this->redirectToRoute('app_admin');
        }

        return $this->render('admin/index.html.twig', [
            'settings' => $settings,
            'columns' => BoardController::columns(),
            'cardsByColumn' => $cardRepository->findGroupedByColumn(),
            'cardForm' => $form,
        ]);
    }

    #[Route('/cards/{id}/edit', name: 'app_admin_card_edit', methods: ['GET', 'POST'])]
    public function editCard(
        Request $request,
        Card $card,
        CardService $cardService,
        AppSettingsService $settingsService,
    ): Response {
        $settings = $settingsService->getSettings();
        $originalColumnKey = $card->getColumnKey();
        $form = $this->createForm(CardType::class, $card, [
            'colors_enabled' => $settings->isCardColorsEnabled(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$settings->isCardColorsEnabled()) {
                $card->setColor('neutral');
            }

            $cardService->updateCard($card, $originalColumnKey);
            $this->addFlash('success', 'Card updated.');

            return $this->redirectToRoute('app_admin');
        }

        return $this->render('admin/card_edit.html.twig', [
            'settings' => $settings,
            'card' => $card,
            'cardForm' => $form,
        ]);
    }

    #[Route('/cards/{id}/delete', name: 'app_admin_card_delete', methods: ['POST'])]
    public function deleteCard(Request $request, Card $card, CardService $cardService): Response
    {
        $this->isCsrfTokenValid('delete-card-'.$card->getId(), (string) $request->request->get('_token')) || throw $this->createAccessDeniedException();
        $cardService->deleteCard($card);
        $this->addFlash('success', 'Card deleted.');

        return $this->redirectToRoute('app_admin');
    }

    #[Route('/cards/{id}/move/{direction}', name: 'app_admin_card_move', methods: ['POST'])]
    public function moveCard(Card $card, string $direction, CardService $cardService, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $this->isCsrfTokenValid('move-card-'.$card->getId(), (string) $request->request->get('_token')) || throw $this->createAccessDeniedException();

        match ($direction) {
            'up' => $cardService->moveUp($card),
            'down' => $cardService->moveDown($card),
            'wip', 'prio1', 'prio2' => $cardService->moveToColumn($card, $direction),
            default => null,
        };

        return $this->redirectToRoute('app_admin');
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
            if ($settings->getSkin() === 'mono') {
                $settings->setCardColorsEnabled(false);
            }

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
