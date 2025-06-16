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
        if (!$user instanceof User) {
            return;
        }

        if (!$user->isVerified()){
            throw new CustomUserMessageAccountStatusException('Votre compte n\'est pas encore vérifié.');
        }

        if ($user->isAnonymized()) {
            throw new CustomUserMessageAccountStatusException('Ce compte a été anonymisé et n\'est plus accessible.');
        }

        if ($user->isDisabled()) {
            throw new CustomUserMessageAccountStatusException('Votre compte est désactivé. Contactez l\'administrateur.');
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
        // Tu peux laisser vide ici
    }
}