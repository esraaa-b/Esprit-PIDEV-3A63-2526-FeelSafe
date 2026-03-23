<?php

namespace App\Service;

use App\Entity\RendezVous;
use App\Enum\ModeRendezVous;

class RendezVousManager
{
    /**
     * Règle 1 : La date du rendez-vous doit être dans le futur (pas dans le passé).
     * Règle 2 : Le mode "presentiel" exige une localisation non vide.
     * Règle 3 : L'utilisateur et le professionnel ne peuvent pas être la même personne.
     */
    public function validate(RendezVous $rdv): bool
    {
        $today = new \DateTimeImmutable('today');
        if ($rdv->getDateRdv() < $today) {
            throw new \InvalidArgumentException(
                'La date du rendez-vous doit être aujourd\'hui ou dans le futur.'
            );
        }

        if (
            $rdv->getMode() === ModeRendezVous::EN_PRESENTIEL &&
            empty(trim($rdv->getLocalisation() ?? ''))
        ) {
            throw new \InvalidArgumentException(
                'Une localisation est obligatoire pour un rendez-vous en présentiel.'
            );
        }

        if (
            $rdv->getUtilisateur() !== null &&
            $rdv->getProfessionnel() !== null &&
            $rdv->getUtilisateur() === $rdv->getProfessionnel()
        ) {
            throw new \InvalidArgumentException(
                'Le patient et le professionnel ne peuvent pas être la même personne.'
            );
        }

        return true;
    }
}