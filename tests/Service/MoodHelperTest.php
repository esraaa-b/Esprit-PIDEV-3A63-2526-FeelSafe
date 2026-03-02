<?php

namespace App\Tests\Service;

use App\Service\MoodHelper;
use PHPUnit\Framework\TestCase;

class MoodHelperTest extends TestCase
{
    public function testOptionsReturnsArray(): void
    {
        $options = MoodHelper::options(); // Appel statique !

        $this->assertIsArray($options);
        $this->assertCount(5, $options);

        // Vérifier la structure du premier élément
        $this->assertArrayHasKey('id', $options[0]);
        $this->assertArrayHasKey('label', $options[0]);
        $this->assertArrayHasKey('icon', $options[0]);
        $this->assertArrayHasKey('color', $options[0]);

        // Vérifier quelques valeurs spécifiques
        $this->assertEquals('very_good', $options[0]['id']);
        $this->assertEquals('good', $options[1]['id']);
        $this->assertEquals('neutral', $options[2]['id']);
    }

    public function testMapReturnsArray(): void
    {
        $map = MoodHelper::map(); // Appel statique !

        $this->assertIsArray($map);
        $this->assertCount(5, $map);

        // Vérifier que les clés sont les IDs
        $this->assertArrayHasKey('very_good', $map);
        $this->assertArrayHasKey('good', $map);
        $this->assertArrayHasKey('neutral', $map);
        $this->assertArrayHasKey('bad', $map);
        $this->assertArrayHasKey('very_bad', $map);

        // Vérifier la structure d'un élément
        $this->assertArrayHasKey('id', $map['very_good']);
        $this->assertArrayHasKey('label', $map['very_good']);
        $this->assertEquals('Très bien', $map['very_good']['label']);
    }
}