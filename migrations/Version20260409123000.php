<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260409123000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute le champ motif a consultation_en_ligne.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE consultation_en_ligne ADD motif VARCHAR(255) NOT NULL DEFAULT 'Consultation generale'");
        $this->addSql("ALTER TABLE consultation_en_ligne ALTER motif DROP DEFAULT");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE consultation_en_ligne DROP motif');
    }
}
