<?php

namespace App\Controller;

use App\Entity\Card;
use App\Repository\CardRepository;
use App\Service\AppSettingsService;
use App\Service\CardService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[IsGranted('ROLE_ADMIN')]
#[Route('/board/manage')]
class BoardManagementController extends AbstractController
{
    public function __construct(private readonly ValidatorInterface $validator)
    {
    }

    #[Route('/cards', name: 'app_board_manage_card_create', methods: ['POST'])]
    public function createCard(
        Request $request,
        CardService $cardService,
        CardRepository $cardRepository,
        AppSettingsService $settingsService,
    ): JsonResponse {
        $payload = $this->decodeRequest($request);

        $card = (new Card())
            ->setTitle((string) ($payload['title'] ?? ''))
            ->setColumnKey((string) ($payload['column'] ?? Card::COLUMN_WIP))
            ->setColor((string) ($payload['color'] ?? 'neutral'));

        $errors = $this->validator->validate($card);
        if (count($errors) > 0) {
            return $this->json(['error' => (string) $errors], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $cardService->createCard($card);

        return $this->boardResponse($cardRepository, $settingsService);
    }

    #[Route('/cards/{id}', name: 'app_board_manage_card_update', methods: ['PATCH'])]
    public function updateCard(
        Request $request,
        Card $card,
        CardService $cardService,
        CardRepository $cardRepository,
        AppSettingsService $settingsService,
    ): JsonResponse {
        $payload = $this->decodeRequest($request);
        $originalColumn = $card->getColumnKey();

        if (array_key_exists('title', $payload)) {
            $card->setTitle((string) $payload['title']);
        }
        if (array_key_exists('color', $payload)) {
            $card->setColor((string) $payload['color']);
        }
        if (array_key_exists('column', $payload)) {
            $card->setColumnKey((string) $payload['column']);
        }

        $errors = $this->validator->validate($card);
        if (count($errors) > 0) {
            return $this->json(['error' => (string) $errors], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $cardService->updateCard($card, $originalColumn);

        return $this->boardResponse($cardRepository, $settingsService);
    }

    #[Route('/cards/{id}', name: 'app_board_manage_card_delete', methods: ['DELETE'])]
    public function deleteCard(
        Card $card,
        CardService $cardService,
        CardRepository $cardRepository,
        AppSettingsService $settingsService,
    ): JsonResponse {
        $cardService->deleteCard($card);

        return $this->boardResponse($cardRepository, $settingsService);
    }

    #[Route('/cards/{id}/move', name: 'app_board_manage_card_move', methods: ['POST'])]
    public function moveCard(
        Request $request,
        Card $card,
        CardService $cardService,
        CardRepository $cardRepository,
        AppSettingsService $settingsService,
    ): JsonResponse {
        $payload = $this->decodeRequest($request);
        $column = (string) ($payload['column'] ?? $card->getColumnKey());
        $position = max(1, (int) ($payload['position'] ?? $card->getPosition()));

        $cardService->moveToPosition($card, $column, $position);

        return $this->boardResponse($cardRepository, $settingsService);
    }

    private function boardResponse(CardRepository $cardRepository, AppSettingsService $settingsService): JsonResponse
    {
        $settings = $settingsService->getSettings();

        return $this->json([
            'html' => $this->renderView('board/_columns.html.twig', [
                'columns' => BoardController::columns(),
                'cardsByColumn' => $cardRepository->findGroupedByColumn(),
                'editable' => true,
                'cardColors' => Card::COLORS,
            ]),
            'boardVersion' => $settings->getBoardVersion(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeRequest(Request $request): array
    {
        $payload = json_decode($request->getContent(), true);

        return is_array($payload) ? $payload : [];
    }
}
