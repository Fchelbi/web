<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260409093000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ancienne migration psychologue remplacee: les psychologues sont maintenant les users avec role Coach.';
    }

    public function up(Schema $schema): void
    {
    }

    public function down(Schema $schema): void
    {
    }
}
