<?php

namespace App\Security\Voter;

use App\Entity\DietDiary;
use App\Entity\Security\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Záznam deníku smí upravit nebo smazat jen jeho vlastník.
 */
final class DietDiaryVoter extends Voter
{
    public const EDIT = 'DIET_DIARY_EDIT';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === self::EDIT && $subject instanceof DietDiary;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        return $user instanceof User && $subject->getUser()->getId() === $user->getId();
    }
}
