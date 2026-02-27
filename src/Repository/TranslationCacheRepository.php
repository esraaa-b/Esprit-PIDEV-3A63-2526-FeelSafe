<?php

namespace App\Repository;

use App\Entity\TranslationCache;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TranslationCache>
 */
class TranslationCacheRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TranslationCache::class);
    }

    public function findCached(string $sourceTextHash, string $targetLang): ?TranslationCache
    {
        return $this->findOneBy(
            ['sourceTextHash' => $sourceTextHash, 'targetLang' => $targetLang],
            ['id' => 'DESC']
        );
    }
}
