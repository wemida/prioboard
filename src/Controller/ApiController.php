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
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api')]
class ApiController extends AbstractController
{
    public function __construct(private readonly ValidatorInterface $validator)
    {
    }

    #[Route('/board', name: 'api_board', methods: ['GET'])]
    public function board(CardRepository $cardRepository, AppSettingsService $settingsService): JsonResponse
    {
        $settings = $settingsService->getSettings();

        return $this->json([
            'settings' => [
                'skin' => $settings->getSkin(),
                'cardColorsEnabled' => $settings->isCardColorsEnabled(),
                'fontSize' => $settings->getFontSize(),
                'refreshInterval' => $settings->getRefreshInterval(),
                'boardVersion' => $settings->getBoardVersion(),
                'updatedAt' => $settings->getUpdatedAt()->format(DATE_ATOM),
            ],
            'columns' => BoardController::columns(),
            'cards' => array_map(
                fn (array $cards) => array_map([$this, 'serializeCard'], $cards),
                $cardRepository->findGroupedByColumn()
            ),
        ]);
    }

    #[Route('/board/meta', name: 'api_board_meta', methods: ['GET'])]
    public function meta(AppSettingsService $settingsService): JsonResponse
    {
        $settings = $settingsService->getSettings();

        return $this->json([
            'boardVersion' => $settings->getBoardVersion(),
            'refreshInterval' => $settings->getRefreshInterval(),
            'updatedAt' => $settings->getUpdatedAt()->format(DATE_ATOM),
        ]);
    }

    #[Route('/cards', name: 'api_card_create', methods: ['POST'])]
    public function createCard(
        Request $request,
        CardService $cardService,
        AppSettingsService $settingsService,
    ): JsonResponse {
        $this->assertApiKey($request, $settingsService);
        $payload = $this->decodeRequest($request);

        $card = (new Card())
            ->setTitle((string) ($payload['title'] ?? ''))
            ->setColumnKey((string) ($payload['column'] ?? Card::COLUMN_WIP))
            ->setColor(isset($payload['color']) ? (string) $payload['color'] : 'neutral');

        if (!$settingsService->getSettings()->isCardColorsEnabled()) {
            $card->setColor('neutral');
        }

        $violations = $this->validator->validate($card);
        if (count($violations) > 0) {
            return $this->json(['error' => (string) $violations], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $cardService->createCard($card);

        return $this->json($this->serializeCard($card), Response::HTTP_CREATED);
    }

    #[Route('/cards/{id}', name: 'api_card_update', methods: ['PUT', 'PATCH'])]
    public function updateCard(
        Request $request,
        Card $card,
        CardService $cardService,
        AppSettingsService $settingsService,
    ): JsonResponse {
        $this->assertApiKey($request, $settingsService);
        $payload = $this->decodeRequest($request);
        $originalColumnKey = $card->getColumnKey();

        if (array_key_exists('title', $payload)) {
            $card->setTitle((string) $payload['title']);
        }
        if (array_key_exists('column', $payload)) {
            $card->setColumnKey((string) $payload['column']);
        }
        if (array_key_exists('color', $payload)) {
            $card->setColor($payload['color'] ? (string) $payload['color'] : null);
        }

        if (!$settingsService->getSettings()->isCardColorsEnabled()) {
            $card->setColor('neutral');
        }

        $violations = $this->validator->validate($card);
        if (count($violations) > 0) {
            return $this->json(['error' => (string) $violations], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $cardService->updateCard($card, $originalColumnKey);

        return $this->json($this->serializeCard($card));
    }

    #[Route('/cards/{id}', name: 'api_card_delete', methods: ['DELETE'])]
    public function deleteCard(Request $request, Card $card, CardService $cardService, AppSettingsService $settingsService): JsonResponse
    {
        $this->assertApiKey($request, $settingsService);
        $cardService->deleteCard($card);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/cards/{id}/move/{direction}', name: 'api_card_move', methods: ['POST'])]
    public function moveCard(
        Request $request,
        Card $card,
        string $direction,
        CardService $cardService,
        AppSettingsService $settingsService,
    ): JsonResponse {
        $this->assertApiKey($request, $settingsService);

        match ($direction) {
            'up' => $cardService->moveUp($card),
            'down' => $cardService->moveDown($card),
            'wip', 'prio1', 'prio2' => $cardService->moveToColumn($card, $direction),
            default => throw $this->createNotFoundException(),
        };

        return $this->json($this->serializeCard($card));
    }

    private function assertApiKey(Request $request, AppSettingsService $settingsService): void
    {
        $settings = $settingsService->getSettings();
        $providedKey = $request->headers->get('X-API-Key');

        if (!$settings->getApiKey() || !hash_equals($settings->getApiKey(), (string) $providedKey)) {
            throw $this->createAccessDeniedException('Invalid API key.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeRequest(Request $request): array
    {
        $payload = json_decode($request->getContent(), true);

        return is_array($payload) ? $payload : [];
    }

    /**
     * @return array<string, int|string|null>
     */
    private function serializeCard(Card $card): array
    {
        return [
            'id' => $card->getId(),
            'title' => $card->getTitle(),
            'column' => $card->getColumnKey(),
            'position' => $card->getPosition(),
            'color' => $card->getColor(),
            'updatedAt' => $card->getUpdatedAt()->format(DATE_ATOM),
        ];
    }
}
