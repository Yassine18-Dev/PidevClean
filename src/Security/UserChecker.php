<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) return;

        if ($user->getStatus() === User::STATUS_BANNED) {
            throw new CustomUserMessageAccountStatusException('Your account is banned.');
        }
        if ($user->getStatus() === User::STATUS_SUSPENDED) {
            throw new CustomUserMessageAccountStatusException('Your account is suspended.');
        }
    }

    public function checkPostAuth(UserInterface $user): void {}
}
