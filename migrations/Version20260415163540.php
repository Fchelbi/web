<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260415163540 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE feedback (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, comment LONGTEXT NOT NULL, sentiment VARCHAR(20) DEFAULT NULL, confidence DOUBLE PRECISION DEFAULT NULL, ai_summary VARCHAR(500) DEFAULT NULL, rating INT DEFAULT NULL, created_at DATETIME NOT NULL, formation_id INT DEFAULT NULL, INDEX IDX_D22944585200282E (formation_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE feedback ADD CONSTRAINT FK_D22944585200282E FOREIGN KEY (formation_id) REFERENCES formation (id) ON DELETE CASCADE');
        $this->addSql('DROP INDEX user_id ON participation');
        $this->addSql('ALTER TABLE participation CHANGE id id INT NOT NULL, CHANGE user_id user_id INT DEFAULT NULL, CHANGE formation_id formation_id INT DEFAULT NULL, CHANGE date_inscription date_inscription DATETIME NOT NULL');
        $this->addSql('ALTER TABLE participation RENAME INDEX formation_id TO IDX_AB55E24F5200282E');
        $this->addSql('ALTER TABLE question CHANGE question_text question_text LONGTEXT NOT NULL, CHANGE points points INT NOT NULL');
        $this->addSql('ALTER TABLE question RENAME INDEX quiz_id TO IDX_B6F7494E853CD175');
        $this->addSql('ALTER TABLE quiz DROP INDEX formation_id, ADD INDEX IDX_A412FA925200282E (formation_id)');
        $this->addSql('ALTER TABLE quiz CHANGE formation_id formation_id INT DEFAULT NULL, CHANGE passing_score passing_score INT NOT NULL');
        $this->addSql('DROP INDEX idx_user_quiz ON quiz_result');
        $this->addSql('ALTER TABLE quiz_result CHANGE quiz_id quiz_id INT DEFAULT NULL, CHANGE score score INT NOT NULL, CHANGE total_points total_points INT NOT NULL, CHANGE passed passed TINYINT NOT NULL, CHANGE completed_at completed_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE quiz_result RENAME INDEX quiz_id TO IDX_FE2E314A853CD175');
        $this->addSql('ALTER TABLE rapport CHANGE id_rapport id_rapport INT NOT NULL, CHANGE id_patient id_patient INT DEFAULT NULL, CHANGE id_coach id_coach INT DEFAULT NULL, CHANGE contenu contenu LONGTEXT NOT NULL, CHANGE recommandations recommandations LONGTEXT NOT NULL, CHANGE nb_seances nb_seances INT NOT NULL, CHANGE score_humeur score_humeur DOUBLE PRECISION NOT NULL, CHANGE periode periode VARCHAR(255) NOT NULL, CHANGE date_creation date_creation DATETIME NOT NULL, CHANGE fichier_pdf fichier_pdf VARCHAR(512) NOT NULL');
        $this->addSql('ALTER TABLE rapport RENAME INDEX id_patient TO IDX_BE34A09CC4477E9B');
        $this->addSql('ALTER TABLE rapport RENAME INDEX id_coach TO IDX_BE34A09CD1DC2CFC');
        $this->addSql('ALTER TABLE reponse CHANGE question_id question_id INT DEFAULT NULL, CHANGE is_correct is_correct TINYINT NOT NULL');
        $this->addSql('ALTER TABLE reponse RENAME INDEX question_id TO IDX_5FB6DEC71E27F6BF');
        $this->addSql('DROP INDEX email ON user');
        $this->addSql('ALTER TABLE user CHANGE id_user id_user INT NOT NULL, CHANGE role role VARCHAR(255) NOT NULL, CHANGE num_tel num_tel VARCHAR(20) NOT NULL, CHANGE photo photo VARCHAR(500) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE feedback DROP FOREIGN KEY FK_D22944585200282E');
        $this->addSql('DROP TABLE feedback');
        $this->addSql('ALTER TABLE participation CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE date_inscription date_inscription DATETIME DEFAULT CURRENT_TIMESTAMP, CHANGE user_id user_id INT NOT NULL, CHANGE formation_id formation_id INT NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX user_id ON participation (user_id, formation_id)');
        $this->addSql('ALTER TABLE participation RENAME INDEX idx_ab55e24f5200282e TO formation_id');
        $this->addSql('ALTER TABLE question CHANGE question_text question_text TEXT NOT NULL, CHANGE points points INT DEFAULT 5');
        $this->addSql('ALTER TABLE question RENAME INDEX idx_b6f7494e853cd175 TO quiz_id');
        $this->addSql('ALTER TABLE quiz DROP INDEX IDX_A412FA925200282E, ADD UNIQUE INDEX formation_id (formation_id)');
        $this->addSql('ALTER TABLE quiz CHANGE passing_score passing_score INT DEFAULT 70, CHANGE formation_id formation_id INT NOT NULL');
        $this->addSql('ALTER TABLE quiz_result CHANGE score score INT DEFAULT 0 NOT NULL, CHANGE total_points total_points INT DEFAULT 0 NOT NULL, CHANGE passed passed TINYINT DEFAULT 0 NOT NULL, CHANGE completed_at completed_at DATETIME DEFAULT CURRENT_TIMESTAMP, CHANGE quiz_id quiz_id INT NOT NULL');
        $this->addSql('CREATE INDEX idx_user_quiz ON quiz_result (user_id, quiz_id)');
        $this->addSql('ALTER TABLE quiz_result RENAME INDEX idx_fe2e314a853cd175 TO quiz_id');
        $this->addSql('ALTER TABLE rapport CHANGE id_rapport id_rapport INT AUTO_INCREMENT NOT NULL, CHANGE contenu contenu TEXT DEFAULT NULL, CHANGE recommandations recommandations TEXT DEFAULT NULL, CHANGE nb_seances nb_seances INT DEFAULT 1, CHANGE score_humeur score_humeur DOUBLE PRECISION DEFAULT \'5\', CHANGE periode periode VARCHAR(255) DEFAULT NULL, CHANGE date_creation date_creation DATETIME DEFAULT CURRENT_TIMESTAMP, CHANGE fichier_pdf fichier_pdf VARCHAR(512) DEFAULT NULL, CHANGE id_patient id_patient INT NOT NULL, CHANGE id_coach id_coach INT NOT NULL');
        $this->addSql('ALTER TABLE rapport RENAME INDEX idx_be34a09cd1dc2cfc TO id_coach');
        $this->addSql('ALTER TABLE rapport RENAME INDEX idx_be34a09cc4477e9b TO id_patient');
        $this->addSql('ALTER TABLE reponse CHANGE is_correct is_correct TINYINT DEFAULT 0, CHANGE question_id question_id INT NOT NULL');
        $this->addSql('ALTER TABLE reponse RENAME INDEX idx_5fb6dec71e27f6bf TO question_id');
        $this->addSql('ALTER TABLE user CHANGE id_user id_user INT AUTO_INCREMENT NOT NULL, CHANGE role role ENUM(\'Patient\', \'Admin\', \'Coach\') NOT NULL, CHANGE num_tel num_tel VARCHAR(20) DEFAULT NULL, CHANGE photo photo VARCHAR(500) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX email ON user (email)');
    }
}
