<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260413143000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute la table consultation_en_ligne compatible avec la table user(id_user) du fichier SQL.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
CREATE TABLE IF NOT EXISTS consultation_en_ligne (
    id INT AUTO_INCREMENT NOT NULL,
    user_id INT NOT NULL,
    psychologue_id INT NOT NULL,
    date_consultation DATETIME NOT NULL,
    motif VARCHAR(255) NOT NULL,
    statut VARCHAR(50) NOT NULL DEFAULT 'en_attente',
    meet_link VARCHAR(255) DEFAULT NULL,
    INDEX IDX_CONSULTATION_USER (user_id),
    INDEX IDX_CONSULTATION_PSY (psychologue_id),
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
SQL);
        $foreignKeys = $this->connection->fetchFirstColumn(
            "SELECT CONSTRAINT_NAME
             FROM information_schema.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'consultation_en_ligne'
               AND CONSTRAINT_TYPE = 'FOREIGN KEY'"
        );

        if (!in_array('FK_CONSULTATION_USER', $foreignKeys, true) && !in_array('fk_consultation_user', $foreignKeys, true)) {
            $this->addSql('ALTER TABLE consultation_en_ligne ADD CONSTRAINT FK_CONSULTATION_USER FOREIGN KEY (user_id) REFERENCES `user` (id_user) ON DELETE CASCADE');
        }

        if (
            !in_array('FK_CONSULTATION_PSY', $foreignKeys, true)
            && !in_array('fk_consultation_psy', $foreignKeys, true)
            && !in_array('fk_consultation_psychologue', $foreignKeys, true)
        ) {
            $this->addSql('ALTER TABLE consultation_en_ligne ADD CONSTRAINT FK_CONSULTATION_PSY FOREIGN KEY (psychologue_id) REFERENCES `user` (id_user) ON DELETE CASCADE');
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

        if (in_array('FK_CONSULTATION_USER', $foreignKeys, true)) {
            $this->addSql('ALTER TABLE consultation_en_ligne DROP FOREIGN KEY FK_CONSULTATION_USER');
        }

        if (in_array('FK_CONSULTATION_PSY', $foreignKeys, true)) {
            $this->addSql('ALTER TABLE consultation_en_ligne DROP FOREIGN KEY FK_CONSULTATION_PSY');
        }

        if (in_array('fk_consultation_user', $foreignKeys, true)) {
            $this->addSql('ALTER TABLE consultation_en_ligne DROP FOREIGN KEY fk_consultation_user');
        }

        if (in_array('fk_consultation_psy', $foreignKeys, true)) {
            $this->addSql('ALTER TABLE consultation_en_ligne DROP FOREIGN KEY fk_consultation_psy');
        }

        if (in_array('fk_consultation_psychologue', $foreignKeys, true)) {
            $this->addSql('ALTER TABLE consultation_en_ligne DROP FOREIGN KEY fk_consultation_psychologue');
        }

        $this->addSql('DROP TABLE consultation_en_ligne');
    }
}
