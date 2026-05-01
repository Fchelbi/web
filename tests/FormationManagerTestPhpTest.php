<?php
namespace App\Tests\Service;

use App\Entity\Formation;
use App\Service\FormationManager;
use PHPUnit\Framework\TestCase;

class FormationManagerTestPhpTest extends TestCase
{
    private FormationManager $manager;

    protected function setUp(): void
    {
        $this->manager = new FormationManager();
    }

    // =========================================================================
    // RÈGLE 1 + 2 : TITRE
    // =========================================================================

    /**
     * Test : une formation avec un titre valide passe la validation.
     */
    public function testTitreValideRetourneTrue(): void
    {
        $formation = new Formation();
        $formation->setTitle('Gestion du Stress');
        $formation->setCategory('Gestion du Stress');

        $this->assertTrue($this->manager->validate($formation));
    }

    /**
     * Test : un titre vide lève une exception (NotBlank).
     */
    public function testTitreVideLeveException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le titre est obligatoire.');

        $formation = new Formation();
        $formation->setTitle('');
        $this->manager->validate($formation);
    }

    /**
     * Test : un titre avec seulement des espaces lève une exception.
     */
    public function testTitreEspacesSeulsLeveException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le titre est obligatoire.');

        $formation = new Formation();
        $formation->setTitle('   ');
        $this->manager->validate($formation);
    }

    /**
     * Test : un titre trop court (< 3 chars) lève une exception (Length min).
     */
    public function testTitreTropCourtLeveException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le titre doit avoir au moins 3 caractères.');

        $formation = new Formation();
        $formation->setTitle('AB');
        $this->manager->validate($formation);
    }

    /**
     * Test : un titre trop long (> 255 chars) lève une exception (Length max).
     */
    public function testTitreTropLongLeveException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le titre ne peut pas dépasser 255 caractères.');

        $formation = new Formation();
        $formation->setTitle(str_repeat('a', 256));
        $this->manager->validate($formation);
    }

    // =========================================================================
    // RÈGLE 3 : CATÉGORIE
    // =========================================================================

    /**
     * Test : une catégorie invalide lève une exception (Choice).
     */
    public function testCategorieInvalideLeveException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Catégorie invalide.');

        $formation = new Formation();
        $formation->setTitle('Formation Test');
        $formation->setCategory('CategorieInexistante');
        $this->manager->validate($formation);
    }

    /**
     * Test : une catégorie null est autorisée.
     */
    public function testCategorieNullAutorisee(): void
    {
        $formation = new Formation();
        $formation->setTitle('Formation Sans Catégorie');
        $formation->setCategory(null);

        $this->assertTrue($this->manager->validate($formation));
    }

    /**
     * Test : toutes les catégories valides passent la validation.
     */
    public function testToutesLesCategoriesValidesPassent(): void
    {
        $categories = [
            'Nutrition',
            'Sport & Fitness',
            'Santé Mentale',
            'Méditation',
            'Gestion du Stress',
            'Autre',
        ];

        foreach ($categories as $cat) {
            $formation = new Formation();
            $formation->setTitle('Formation ' . $cat);
            $formation->setCategory($cat);
            $this->assertTrue($this->manager->validate($formation));
        }
    }

    // =========================================================================
    // RÈGLE 4 : DESCRIPTION
    // =========================================================================

    /**
     * Test : une description trop longue (> 2000 chars) lève une exception.
     */
    public function testDescriptionTropLongueLeveException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La description ne peut pas dépasser 2000 caractères.');

        $formation = new Formation();
        $formation->setTitle('Formation Longue');
        $formation->setDescription(str_repeat('a', 2001));
        $this->manager->validate($formation);
    }

    /**
     * Test : une description de exactement 2000 chars est valide.
     */
    public function testDescription2000CharsExactEstValide(): void
    {
        $formation = new Formation();
        $formation->setTitle('Formation Limite');
        $formation->setDescription(str_repeat('a', 2000));

        $this->assertTrue($this->manager->validate($formation));
    }

    /**
     * Test : une description null est autorisée.
     */
    public function testDescriptionNullAutorisee(): void
    {
        $formation = new Formation();
        $formation->setTitle('Formation Sans Description');
        $formation->setDescription(null);

        $this->assertTrue($this->manager->validate($formation));
    }

    // =========================================================================
    // SCORE DE COMPLÉTUDE
    // =========================================================================

    /**
     * Test : formation avec seulement le titre → score = 25%.
     */
    public function testScoreTitreSeul(): void
    {
        $formation = new Formation();
        $formation->setTitle('Ma Formation');

        $this->assertEquals(25, $this->manager->calculateCompletionScore($formation));
    }

    /**
     * Test : formation complète (titre + description + catégorie + vidéo) → score = 100%.
     */
    public function testScoreFormationComplete(): void
    {
        $formation = new Formation();
        $formation->setTitle('Formation Complète');
        $formation->setDescription('Une description complète.');
        $formation->setCategory('Nutrition');
        $formation->setVideoUrl('https://www.youtube.com/watch?v=dQw4w9WgXcQ');

        $this->assertEquals(100, $this->manager->calculateCompletionScore($formation));
    }

    /**
     * Test : formation sans titre ni description → score = 0%.
     */
    public function testScoreFormationVide(): void
    {
        $formation = new Formation();
        $formation->setTitle('');

        $this->assertEquals(0, $this->manager->calculateCompletionScore($formation));
    }
}