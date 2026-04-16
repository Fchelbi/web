<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260408235242 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ancienne migration consultation remplacee par Version20260413143000 pour compatibilite avec user(id_user).';
    }

    public function up(Schema $schema): void
    {
    }

    public function down(Schema $schema): void
    {
    }
}
