<?php

namespace App\Tests;

use App\Entity\JournalEmotionnel;
use App\Enum\EmotionEnum;
use App\Service\JournalEmotionnelManager;
use PHPUnit\Framework\TestCase;

class JournalEmotionnelManagerTest extends TestCase
{
    private JournalEmotionnelManager $manager;

    protected function setUp(): void
    {
        $this->manager = new JournalEmotionnelManager();
    }

    // ✅ Test 1 : Entrée valide avec contenu texte
    public function testEntreeValideAvecContenu(): void
    {
        $journal = new JournalEmotionnel();
        $journal->setEmotion(EmotionEnum::BIEN);
        $journal->setContenu('Je me sens bien aujourd\'hui.');

        $this->assertTrue($this->manager->validate($journal));
    }

    // ✅ Test 2 : Entrée valide avec image uniquement (sans contenu texte)
    public function testEntreeValideAvecImageSeulement(): void
    {
        $journal = new JournalEmotionnel();
        $journal->setEmotion(EmotionEnum::NEUTRE);
        $journal->setImage('uploads/journal/photo.jpg');

        $this->assertTrue($this->manager->validate($journal));
    }

    // ✅ Test 3 : Entrée valide avec audio uniquement
    public function testEntreeValideAvecAudioSeulement(): void
    {
        $journal = new JournalEmotionnel();
        $journal->setEmotion(EmotionEnum::TRES_BIEN);
        $journal->setAudio('uploads/journal/note.mp3');

        $this->assertTrue($this->manager->validate($journal));
    }

    // ❌ Test 4 : Contenu trop court (moins de 6 caractères)
    public function testContenuTropCourt(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('au moins 6 caractères');

        $journal = new JournalEmotionnel();
        $journal->setEmotion(EmotionEnum::PAS_BIEN);
        $journal->setContenu('Mal');

        $this->manager->validate($journal);
    }

    // ❌ Test 5 : Aucun média fourni (ni texte, ni image, ni audio)
    public function testAucunMediaFourni(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Au moins un contenu');

        $journal = new JournalEmotionnel();
        $journal->setEmotion(EmotionEnum::TRES_MAL);

        $this->manager->validate($journal);
    }

    // ❌ Test 6 : Contenu composé uniquement d'espaces (équivalent à vide)
    public function testContenuEspacesUniquement(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $journal = new JournalEmotionnel();
        $journal->setEmotion(EmotionEnum::NEUTRE);
        $journal->setContenu('     ');

        $this->manager->validate($journal);
    }
}

