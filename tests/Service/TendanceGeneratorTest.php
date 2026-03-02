public function testGenerateForMonthWithNoJournals(): void
{
// Création des mocks
$em = $this->createMock(EntityManagerInterface::class);
$repository = $this->createMock(EntityRepository::class);
$queryBuilder = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
$query = $this->createMock(\Doctrine\ORM\AbstractQuery::class);

// Configuration du repository
$em->method('getRepository')
->with(JournalEmotionnel::class)
->willReturn($repository);

$repository->method('createQueryBuilder')
->with('j')
->willReturn($queryBuilder);

$queryBuilder->method('where')->willReturnSelf();
$queryBuilder->method('andWhere')->willReturnSelf();
$queryBuilder->method('setParameter')->willReturnSelf();
$queryBuilder->method('getQuery')->willReturn($query);

// Simuler aucun journal trouvé
$query->method('getResult')->willReturn([]);

// Simuler la suppression
$qb2 = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
$em->method('createQueryBuilder')->willReturn($qb2);
$qb2->method('delete')->willReturnSelf();
$qb2->method('where')->willReturnSelf();
$qb2->method('andWhere')->willReturnSelf();
$qb2->method('setParameter')->willReturnSelf();
$qb2->method('getQuery')->willReturn($query);

$user = new Utilisateur();
$generator = new TendanceGenerator($em);
$generator->generateForMonth($user, 3, 2026);

$this->assertTrue(true);
}