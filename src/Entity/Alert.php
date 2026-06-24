<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\AlertType;
use App\Repository\AlertRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AlertRepository::class)]
class Alert
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, enumType: AlertType::class)]
    private ?AlertType $type = null;

    #[ORM\Column]
    private ?float $thresholdValue = null;

    #[ORM\Column]
    private bool $isActive = true;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $triggeredAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?WatchedAddress $watchedAddress = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->isActive = true;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): ?AlertType
    {
        return $this->type;
    }

    public function setType(AlertType $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getThresholdValue(): ?float
    {
        return $this->thresholdValue;
    }

    public function setThresholdValue(float $thresholdValue): static
    {
        $this->thresholdValue = $thresholdValue;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function getTriggeredAt(): ?\DateTimeImmutable
    {
        return $this->triggeredAt;
    }

    public function setTriggeredAt(\DateTimeImmutable $triggeredAt): static
    {
        $this->triggeredAt = $triggeredAt;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getWatchedAddress(): ?WatchedAddress
    {
        return $this->watchedAddress;
    }

    public function setWatchedAddress(?WatchedAddress $watchedAddress): static
    {
        $this->watchedAddress = $watchedAddress;

        return $this;
    }
}
