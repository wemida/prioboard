<?php

namespace App\Service;

use App\Entity\Card;
use App\Repository\CardRepository;
use Doctrine\ORM\EntityManagerInterface;

class CardService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CardRepository $cardRepository,
        private readonly AppSettingsService $settingsService,
    ) {
    }

    public function createCard(Card $card): void
    {
        $card->setPosition($this->cardRepository->getMaxPosition($card->getColumnKey()) + 1);
        $this->entityManager->persist($card);
        $this->entityManager->flush();
        $this->settingsService->touchBoard();
    }

    public function updateCard(Card $card, string $originalColumnKey): void
    {
        if ($originalColumnKey !== $card->getColumnKey()) {
            $card->setPosition($this->cardRepository->getMaxPosition($card->getColumnKey()) + 1);
            $this->normalizePositions($originalColumnKey);
        }

        $this->entityManager->flush();
        $this->normalizePositions($card->getColumnKey());
        $this->settingsService->touchBoard();
    }

    public function deleteCard(Card $card): void
    {
        $columnKey = $card->getColumnKey();
        $this->entityManager->remove($card);
        $this->entityManager->flush();
        $this->normalizePositions($columnKey);
        $this->settingsService->touchBoard();
    }

    public function moveToColumn(Card $card, string $columnKey): void
    {
        $this->moveToPosition($card, $columnKey, $this->cardRepository->getMaxPosition($columnKey) + 1);
    }

    public function moveUp(Card $card): void
    {
        $cards = $this->cardRepository->findByColumnOrdered($card->getColumnKey());
        $this->moveWithinColumn($cards, $card, -1);
    }

    public function moveDown(Card $card): void
    {
        $cards = $this->cardRepository->findByColumnOrdered($card->getColumnKey());
        $this->moveWithinColumn($cards, $card, 1);
    }

    private function moveWithinColumn(array $cards, Card $card, int $direction): void
    {
        $index = array_search($card, $cards, true);

        if (!is_int($index)) {
            return;
        }

        $targetIndex = $index + $direction;
        if (!isset($cards[$targetIndex])) {
            return;
        }

        [$cards[$index], $cards[$targetIndex]] = [$cards[$targetIndex], $cards[$index]];

        foreach (array_values($cards) as $position => $columnCard) {
            $columnCard->setPosition($position + 1);
        }

        $this->entityManager->flush();
        $this->settingsService->touchBoard();
    }

    public function normalizePositions(string $columnKey): void
    {
        foreach ($this->cardRepository->findByColumnOrdered($columnKey) as $index => $card) {
            $card->setPosition($index + 1);
        }

        $this->entityManager->flush();
    }

    public function moveToPosition(Card $card, string $columnKey, int $position): void
    {
        $previousColumn = $card->getColumnKey();
        $card->setColumnKey($columnKey);

        $cards = $this->cardRepository->findByColumnOrdered($columnKey);
        $cards = array_values(array_filter($cards, static fn (Card $existingCard): bool => $existingCard->getId() !== $card->getId()));

        $targetIndex = max(0, min($position - 1, count($cards)));
        array_splice($cards, $targetIndex, 0, [$card]);

        foreach ($cards as $index => $columnCard) {
            $columnCard->setColumnKey($columnKey);
            $columnCard->setPosition($index + 1);
        }

        $this->entityManager->flush();

        if ($previousColumn !== $columnKey) {
            $this->normalizePositions($previousColumn);
        }

        $this->settingsService->touchBoard();
    }
}
