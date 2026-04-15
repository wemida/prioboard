<?php

namespace App\Entity;

use App\Repository\CardRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CardRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Card
{
    public const COLUMN_WIP = 'wip';
    public const COLUMN_PRIO_1 = 'prio1';
    public const COLUMN_PRIO_2 = 'prio2';

    public const COLUMNS = [
        self::COLUMN_WIP,
        self::COLUMN_PRIO_1,
        self::COLUMN_PRIO_2,
    ];

    public const COLORS = [
        'neutral',
        'red',
        'orange',
        'yellow',
        'green',
        'blue',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    private string $title = '';

    #[ORM\Column(length: 20)]
    #[Assert\Choice(choices: self::COLUMNS)]
    private string $columnKey = self::COLUMN_WIP;

    #[ORM\Column]
    private int $position = 0;

    #[ORM\Column(length: 20, nullable: true)]
    #[Assert\Choice(choices: self::COLORS)]
    private ?string $color = 'neutral';

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = trim($title);

        return $this;
    }

    public function getColumnKey(): string
    {
        return $this->columnKey;
    }

    public function setColumnKey(string $columnKey): self
    {
        $this->columnKey = $columnKey;

        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): self
    {
        $this->position = $position;

        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(?string $color): self
    {
        $this->color = $color ?: 'neutral';

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
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
