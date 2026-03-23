<?php

namespace App\Security\Voter;

use App\Entity\JournalEmotionnel;
use App\Entity\Utilisateur;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class JournalEmotionnelVoter extends Voter
{
    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === 'OWNER'
            && $subject instanceof JournalEmotionnel;
    }

    protected function voteOnAttribute(
        string $attribute,
        mixed $subject,
        TokenInterface $token
    ): bool {
        $user = $token->getUser();

        if (!$user instanceof Utilisateur) {
            return false;
        }

        /** @var JournalEmotionnel $journal */
        $journal = $subject;

        return $journal->getUtilisateur() !== null && $journal->getUtilisateur()->getId() === $user->getId();
    }
}
