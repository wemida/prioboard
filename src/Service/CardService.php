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
        $previousColumn = $card->getColumnKey();
        $card->setColumnKey($columnKey);
        $card->setPosition($this->cardRepository->getMaxPosition($columnKey) + 1);
        $this->entityManager->flush();
        $this->normalizePositions($previousColumn);
        $this->normalizePositions($columnKey);
        $this->settingsService->touchBoard();
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
}
