<?php

declare(strict_types=1);

namespace App\Service\Alert;

use App\Entity\Alert;
use App\Enum\AlertType;
use App\Repository\AlertRepository;
use App\Service\Bitcoin\AddressInfoProvider;
use Doctrine\ORM\EntityManagerInterface;

final class AlertChecker
{
    public function __construct(
        private readonly AlertRepository $alertRepository,
        private readonly AddressInfoProvider $addressInfoProvider,
        private readonly EntityManagerInterface $em,
        private readonly AlertNotifier $alertNotifier,
    ) {
    }

    public function checkForAddress(int $watchedAddressId, string $address): void
    {
        $alerts = $this->alertRepository->findActiveByWatchedAddress($watchedAddressId);

        if (empty($alerts)) {
            return;
        }

        $balance = $this->addressInfoProvider->getBalance($address);
        $balanceBtc = $balance['balance_btc'];

        foreach ($alerts as $alert) {
            if ($this->shouldTrigger($alert, $balanceBtc)) {
                $alert->setTriggeredAt(new \DateTimeImmutable());
                $alert->setIsActive(false);
                $this->alertNotifier->notify($alert);
                $this->em->flush();
            }
        }
    }

    private function shouldTrigger(Alert $alert, float $balanceBtc): bool
    {
        return match ($alert->getType()) {
            AlertType::BalanceAbove => $balanceBtc > $alert->getThresholdValue(),
            AlertType::BalanceBelow => $balanceBtc < $alert->getThresholdValue(),
            default => false,
        };
    }
}
