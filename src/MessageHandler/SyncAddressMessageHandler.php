<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\SyncAddressMessage;
use App\Service\Alert\AlertChecker;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class SyncAddressMessageHandler
{
    public function __construct(
        private readonly AlertChecker $alertChecker,
    ) {
    }

    public function __invoke(SyncAddressMessage $message): void
    {
        $this->alertChecker->checkForAddress(
            $message->watchedAddressId,
            $message->address,
        );
    }
}
