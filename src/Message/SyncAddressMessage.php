<?php

declare(strict_types=1);

namespace App\Message;

final class SyncAddressMessage
{
    public function __construct(
        public readonly int $watchedAddressId,
        public readonly string $address,
    ) {
    }
}
