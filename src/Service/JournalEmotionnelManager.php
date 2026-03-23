<?php

namespace App\Service;

use App\Entity\JournalEmotionnel;

class JournalEmotionnelManager
{
    /**
     * Règle 1 : Le contenu, s'il est renseigné, doit contenir au moins 6 caractères.
     * Règle 2 : L'entrée doit être associée à un utilisateur.
     * Règle 3 : Au moins un média (contenu, image ou audio) doit être fourni.
     */
    public function validate(JournalEmotionnel $journal): bool
    {
        $contenu = $journal->getContenu();
        if ($contenu !== null && mb_strlen(trim($contenu)) < 6) {
            throw new \InvalidArgumentException(
                'Le contenu doit contenir au moins 6 caractères.'
            );
        }

        if ($contenu === null && $journal->getImage() === null && $journal->getAudio() === null) {
            throw new \InvalidArgumentException(
                'Au moins un contenu (texte, image ou audio) est requis.'
            );
        }

        return true;
    }
}