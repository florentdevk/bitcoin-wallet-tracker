<?php

declare(strict_types=1);

namespace App;

use App\Message\SyncAddressMessage;
use App\Repository\WatchedAddressRepository;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule as SymfonySchedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;
use Symfony\Contracts\Cache\CacheInterface;

#[AsSchedule]
class Schedule implements ScheduleProviderInterface
{
    public function __construct(
        private readonly CacheInterface $cache,
        private readonly WatchedAddressRepository $watchedAddressRepository,
    ) {
    }

    public function getSchedule(): SymfonySchedule
    {
        $schedule = (new SymfonySchedule())
            ->stateful($this->cache)
            ->processOnlyLastMissedRun(true);

        foreach ($this->watchedAddressRepository->findAll() as $address) {
            $schedule->add(
                RecurringMessage::every('5 minutes', new SyncAddressMessage(
                    $address->getId(),
                    $address->getAddress(),
                ))
            );
        }

        return $schedule;
    }
}
