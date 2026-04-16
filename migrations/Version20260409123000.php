<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260409123000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ancienne migration motif remplacee par la creation complete de consultation_en_ligne.';
    }

    public function up(Schema $schema): void
    {
    }

    public function down(Schema $schema): void
    {
    }
}
