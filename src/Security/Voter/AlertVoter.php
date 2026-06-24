<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Alert;
use App\Entity\User;
use App\Entity\WatchedAddress;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/** @extends Voter<string, WatchedAddress|Alert> */
final class AlertVoter extends Voter
{
    public const CREATE = 'ALERT_CREATE';
    public const DELETE = 'ALERT_DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return match ($attribute) {
            self::CREATE => $subject instanceof WatchedAddress,
            self::DELETE => $subject instanceof Alert,
            default => false,
        };
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        return match ($attribute) {
            self::CREATE => $subject->getOwner() === $user,
            self::DELETE => $subject->getWatchedAddress()?->getOwner() === $user,
            default => false,
        };
    }
}
