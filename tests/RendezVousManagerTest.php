<?php

namespace App\Tests;

use App\Entity\RendezVous;
use App\Entity\Utilisateur;
use App\Enum\ModeRendezVous;
use App\Enum\StatutRendezVous;
use App\Service\RendezVousManager;
use PHPUnit\Framework\TestCase;

class RendezVousManagerTest extends TestCase
{
    private RendezVousManager $manager;

    protected function setUp(): void
    {
        $this->manager = new RendezVousManager();
    }

    private function makeRdv(string $dateOffset = '+1 day'): RendezVous
    {
        $rdv = new RendezVous();
        $rdv->setDateRdv(new \DateTimeImmutable($dateOffset));
        $rdv->setHeureRdv(new \DateTimeImmutable('10:00'));
        $rdv->setMode(ModeRendezVous::EN_LIGNE); // ajuster selon votre enum
        $rdv->setStatut(StatutRendezVous::HONORE); // ajuster selon votre enum
        return $rdv;
    }

    // ✅ Test 1 : Rendez-vous valide en ligne dans le futur
    public function testRendezVousValideEnLigne(): void
    {
        $rdv = $this->makeRdv('+3 days');

        $this->assertTrue($this->manager->validate($rdv));
    }

    // ✅ Test 2 : Rendez-vous présentiel valide avec localisation
    public function testRendezVousPresentielValide(): void
    {
        $rdv = $this->makeRdv('+5 days');
        $rdv->setMode(ModeRendezVous::EN_PRESENTIEL); // ajuster selon votre enum
        $rdv->setLocalisation('Cabinet médical, 12 rue de la Paix, Tunis');

        $this->assertTrue($this->manager->validate($rdv));
    }

    // ❌ Test 3 : Date dans le passé
    public function testDateDansLePasse(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('dans le futur');

        $rdv = $this->makeRdv('-1 day');
        $this->manager->validate($rdv);
    }

    // ❌ Test 4 : Présentiel sans localisation
    public function testPresentielSansLocalisation(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('localisation est obligatoire');

        $rdv = $this->makeRdv('+2 days');
        $rdv->setMode(ModeRendezVous::EN_PRESENTIEL);
        // pas de localisation définie

        $this->manager->validate($rdv);
    }

    // ❌ Test 5 : Patient et professionnel sont la même personne
    public function testPatientEtProfessionnelIdentiques(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('même personne');

        $utilisateur = new Utilisateur();
        $utilisateur->setNom('zouiten');
        $utilisateur->setPrenom('ezzeddine');
        $utilisateur->setEmail('ezzeddine@test.com');

        $rdv = $this->makeRdv('+1 day');
        $rdv->setUtilisateur($utilisateur);
        $rdv->setProfessionnel($utilisateur); // même objet

        $this->manager->validate($rdv);
    }

    // ✅ Test 6 : Utilisateur et professionnel différents — valide
    public function testPatientEtProfessionnelDifferents(): void
    {
        $patient = new Utilisateur();
        $patient->setNom('zouiten');
        $patient->setPrenom('ezzeddine');
        $patient->setEmail('ezzeddine@test.com');

        $pro = new Utilisateur();
        $pro->setNom('Louini');
        $pro->setPrenom('Nawel');
        $pro->setEmail('nawel@clinique.com');

        $rdv = $this->makeRdv('+1 day');
        $rdv->setUtilisateur($patient);
        $rdv->setProfessionnel($pro);

        $this->assertTrue($this->manager->validate($rdv));
    }
}