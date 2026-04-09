<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260409093000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute la table psychologue et relie consultation_en_ligne a psychologue.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE psychologue (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, specialite VARCHAR(100) NOT NULL, telephone VARCHAR(20) NOT NULL, description LONGTEXT NOT NULL, UNIQUE INDEX UNIQ_D6CB0887A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE psychologue ADD CONSTRAINT FK_3D8FEA25A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE consultation_en_ligne ADD psychologue_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE consultation_en_ligne ADD CONSTRAINT FK_4EB5017AB4466618 FOREIGN KEY (psychologue_id) REFERENCES psychologue (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_4EB5017465459D3 ON consultation_en_ligne (psychologue_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE consultation_en_ligne DROP FOREIGN KEY FK_4EB5017AB4466618');
        $this->addSql('DROP INDEX IDX_4EB5017465459D3 ON consultation_en_ligne');
        $this->addSql('ALTER TABLE consultation_en_ligne DROP COLUMN psychologue_id');
        $this->addSql('ALTER TABLE psychologue DROP FOREIGN KEY FK_3D8FEA25A76ED395');
        $this->addSql('DROP TABLE psychologue');
    }
}
