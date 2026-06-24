<?php

declare(strict_types=1);

namespace App\Service\Alert;

use App\Entity\Alert;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

final class AlertNotifier
{
    public function __construct(
        private readonly MailerInterface $mailer,
    ) {
    }

    public function notify(Alert $alert): void
    {
        $user = $alert->getWatchedAddress()?->getOwner();
        $address = $alert->getWatchedAddress()?->getAddress();

        if (!$user || !$address) {
            return;
        }

        $email = (new Email())
            ->from('noreply@bitcoin-wallet-tracker.com')
            ->to($user->getEmail())
            ->subject('Bitcoin Alert Triggered')
            ->html(\sprintf(
                '<h1>Alert Triggered</h1>
                <p>Your alert for address <strong>%s</strong> has been triggered.</p>
                <p>Type: <strong>%s</strong></p>
                <p>Threshold: <strong>%s BTC</strong></p>',
                $address,
                $alert->getType()?->value,
                $alert->getThresholdValue(),
            ));

        $this->mailer->send($email);
    }
}
