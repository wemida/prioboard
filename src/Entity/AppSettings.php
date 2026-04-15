<?php

namespace App\Entity;

use App\Repository\AppSettingsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AppSettingsRepository::class)]
#[ORM\HasLifecycleCallbacks]
class AppSettings
{
    public const SKIN_COLOR = 'color';
    public const SKIN_MONO = 'mono';

    public const FONT_SMALL = 'small';
    public const FONT_MEDIUM = 'medium';
    public const FONT_LARGE = 'large';

    public const SKINS = [
        self::SKIN_COLOR,
        self::SKIN_MONO,
    ];

    public const FONT_SIZES = [
        self::FONT_SMALL,
        self::FONT_MEDIUM,
        self::FONT_LARGE,
    ];

    #[ORM\Id]
    #[ORM\Column]
    private int $id = 1;

    #[ORM\Column(length: 20)]
    #[Assert\Choice(choices: self::SKINS)]
    private string $skin = self::SKIN_COLOR;

    #[ORM\Column]
    private bool $cardColorsEnabled = true;

    #[ORM\Column(length: 20)]
    #[Assert\Choice(choices: self::FONT_SIZES)]
    private string $fontSize = self::FONT_MEDIUM;

    #[ORM\Column]
    #[Assert\Range(min: 10, max: 600)]
    private int $refreshInterval = 30;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $apiKey = null;

    #[ORM\Column]
    private int $boardVersion = 1;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getSkin(): string
    {
        return $this->skin;
    }

    public function setSkin(string $skin): self
    {
        $this->skin = $skin;

        return $this;
    }

    public function isCardColorsEnabled(): bool
    {
        return $this->cardColorsEnabled;
    }

    public function setCardColorsEnabled(bool $cardColorsEnabled): self
    {
        $this->cardColorsEnabled = $cardColorsEnabled;

        return $this;
    }

    public function getFontSize(): string
    {
        return $this->fontSize;
    }

    public function setFontSize(string $fontSize): self
    {
        $this->fontSize = $fontSize;

        return $this;
    }

    public function getRefreshInterval(): int
    {
        return $this->refreshInterval;
    }

    public function setRefreshInterval(int $refreshInterval): self
    {
        $this->refreshInterval = $refreshInterval;

        return $this;
    }

    public function getApiKey(): ?string
    {
        return $this->apiKey;
    }

    public function setApiKey(?string $apiKey): self
    {
        $this->apiKey = $apiKey ? trim($apiKey) : null;

        return $this;
    }

    public function getBoardVersion(): int
    {
        return $this->boardVersion;
    }

    public function bumpBoardVersion(): self
    {
        ++$this->boardVersion;

        return $this;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
