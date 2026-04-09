<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260408235242 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Synchronise la table consultation_en_ligne avec la fonctionnalite Consultation Symfony 6.4.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE consultation_en_ligne CHANGE meet_link meet_link VARCHAR(255) DEFAULT NULL');

        $foreignKeys = $this->connection->fetchFirstColumn(
            "SELECT CONSTRAINT_NAME
             FROM information_schema.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'consultation_en_ligne'
               AND CONSTRAINT_TYPE = 'FOREIGN KEY'"
        );

        $indexes = $this->connection->fetchFirstColumn(
            "SELECT INDEX_NAME
             FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'consultation_en_ligne'"
        );

        if (in_array('FK_user', $foreignKeys, true)) {
            $this->addSql('ALTER TABLE consultation_en_ligne DROP FOREIGN KEY `FK_user`');
        }

        if (in_array('FK_4EB5017A76ED395', $foreignKeys, true)) {
            $this->addSql('ALTER TABLE consultation_en_ligne DROP FOREIGN KEY FK_4EB5017A76ED395');
        }

        if (in_array('fk_user', $indexes, true)) {
            $this->addSql('ALTER TABLE consultation_en_ligne DROP INDEX fk_user');
        }

        if (in_array('FK_4EB5017A76ED395', $indexes, true)) {
            $this->addSql('ALTER TABLE consultation_en_ligne DROP INDEX FK_4EB5017A76ED395');
        }

        if (!in_array('IDX_4EB5017A76ED395', $indexes, true)) {
            $this->addSql('ALTER TABLE consultation_en_ligne ADD INDEX IDX_4EB5017A76ED395 (user_id)');
        }

        $foreignKeys = $this->connection->fetchFirstColumn(
            "SELECT CONSTRAINT_NAME
             FROM information_schema.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'consultation_en_ligne'
               AND CONSTRAINT_TYPE = 'FOREIGN KEY'"
        );

        if (!in_array('FK_4EB5017A76ED395', $foreignKeys, true)) {
            $this->addSql('ALTER TABLE consultation_en_ligne ADD CONSTRAINT FK_4EB5017A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        }
    }

    public function down(Schema $schema): void
    {
        $foreignKeys = $this->connection->fetchFirstColumn(
            "SELECT CONSTRAINT_NAME
             FROM information_schema.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'consultation_en_ligne'
               AND CONSTRAINT_TYPE = 'FOREIGN KEY'"
        );

        $indexes = $this->connection->fetchFirstColumn(
            "SELECT INDEX_NAME
             FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'consultation_en_ligne'"
        );

        if (in_array('FK_4EB5017A76ED395', $foreignKeys, true)) {
            $this->addSql('ALTER TABLE consultation_en_ligne DROP FOREIGN KEY FK_4EB5017A76ED395');
        }

        if (in_array('IDX_4EB5017A76ED395', $indexes, true)) {
            $this->addSql('ALTER TABLE consultation_en_ligne DROP INDEX IDX_4EB5017A76ED395');
        }

        if (!in_array('fk_user', $indexes, true)) {
            $this->addSql('ALTER TABLE consultation_en_ligne ADD INDEX fk_user (user_id)');
        }

        $foreignKeys = $this->connection->fetchFirstColumn(
            "SELECT CONSTRAINT_NAME
             FROM information_schema.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'consultation_en_ligne'
               AND CONSTRAINT_TYPE = 'FOREIGN KEY'"
        );

        if (!in_array('FK_user', $foreignKeys, true)) {
            $this->addSql('ALTER TABLE consultation_en_ligne ADD CONSTRAINT `FK_user` FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        }
    }
}
