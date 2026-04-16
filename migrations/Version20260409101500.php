<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260409101500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ancienne migration de donnees psychologue remplacee par la table user(role Coach) du fichier SQL.';
    }

    public function up(Schema $schema): void
    {
    }

    public function down(Schema $schema): void
    {
    }
}
