<?php

namespace App\Controller;

use App\Entity\Card;
use App\Repository\CardRepository;
use App\Service\AppSettingsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class BoardController extends AbstractController
{
    #[Route('/', name: 'app_board')]
    public function index(CardRepository $cardRepository, AppSettingsService $settingsService, AuthenticationUtils $authenticationUtils): Response
    {
        $settings = $settingsService->getSettings();

        return $this->render('board/index.html.twig', [
            'settings' => $settings,
            'columns' => self::columns(),
            'cardsByColumn' => $cardRepository->findGroupedByColumn(),
            'editable' => $this->isGranted('ROLE_ADMIN'),
            'cardColors' => Card::COLORS,
            'last_username' => $authenticationUtils->getLastUsername(),
            'error' => $authenticationUtils->getLastAuthenticationError(),
        ]);
    }

    /**
     * @return array<string, string>
     */
    public static function columns(): array
    {
        return [
            Card::COLUMN_WIP => 'WIP',
            Card::COLUMN_PRIO_1 => 'Prio 1',
            Card::COLUMN_PRIO_2 => 'Prio 2',
        ];
    }

}
