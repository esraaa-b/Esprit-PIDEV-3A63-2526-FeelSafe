<?php

namespace App\EventListener;

use App\Entity\JournalEmotionnel;
use App\Service\TendanceGenerator;
use Doctrine\ORM\Events;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;

#[AsDoctrineListener(event: Events::postRemove)]
class JournalEmotionnelListener
{
    public function __construct(
        private TendanceGenerator $tendanceGenerator
    ) {}

    public function postRemove(PostRemoveEventArgs $args): void
    {
        $entity = $args->getObject();
        
        if (!$entity instanceof JournalEmotionnel) {
            return;
        }

        $utilisateur = $entity->getUtilisateur();
        $mois = (int) $entity->getDateCreation()->format('n');
        $annee = (int) $entity->getDateCreation()->format('Y');

        // Régénérer les tendances pour le mois concerné
        $this->tendanceGenerator->generateForMonth($utilisateur, $mois, $annee);
    }
}