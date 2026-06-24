<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\TransactionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TransactionRepository::class)]
class Transaction
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 64, unique: true)]
    private ?string $txid = null;

    #[ORM\Column]
    private ?float $amount = null;

    #[ORM\Column]
    private int $confirmations = 0;

    #[ORM\Column]
    private ?\DateTimeImmutable $detectedAt = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?WatchedAddress $watchedAddress = null;

    public function __construct()
    {
        $this->detectedAt = new \DateTimeImmutable();
        $this->confirmations = 0;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTxid(): ?string
    {
        return $this->txid;
    }

    public function setTxid(string $txid): static
    {
        $this->txid = $txid;

        return $this;
    }

    public function getAmount(): ?float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): static
    {
        $this->amount = $amount;

        return $this;
    }

    public function getConfirmations(): int
    {
        return $this->confirmations;
    }

    public function setConfirmations(int $confirmations): static
    {
        $this->confirmations = $confirmations;

        return $this;
    }

    public function getDetectedAt(): ?\DateTimeImmutable
    {
        return $this->detectedAt;
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
