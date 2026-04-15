<?php

namespace App\Controller;

use App\Entity\Card;
use App\Repository\CardRepository;
use App\Service\AppSettingsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class BoardController extends AbstractController
{
    #[Route('/', name: 'app_board')]
    public function index(CardRepository $cardRepository, AppSettingsService $settingsService): Response
    {
        $settings = $settingsService->getSettings();

        return $this->render('board/index.html.twig', [
            'settings' => $settings,
            'columns' => $this->getColumns(),
            'cardsByColumn' => $cardRepository->findGroupedByColumn(),
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

    /**
     * @return array<string, string>
     */
    private function getColumns(): array
    {
        return self::columns();
    }
}
