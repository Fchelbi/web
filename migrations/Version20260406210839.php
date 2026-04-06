<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260406210839 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE calls CHANGE id_call id_call INT NOT NULL, CHANGE id_caller id_caller INT DEFAULT NULL, CHANGE id_receiver id_receiver INT DEFAULT NULL, CHANGE status status VARCHAR(255) NOT NULL, CHANGE date_appel date_appel DATETIME NOT NULL, CHANGE duree_secondes duree_secondes INT NOT NULL, CHANGE caller_ip caller_ip VARCHAR(50) NOT NULL, CHANGE caller_port caller_port INT NOT NULL');
        $this->addSql('ALTER TABLE calls RENAME INDEX id_caller TO IDX_DAA35C8F2B7BC24A');
        $this->addSql('ALTER TABLE calls RENAME INDEX id_receiver TO IDX_DAA35C8F6D636003');
        $this->addSql('DROP INDEX session_id ON chat_history');
        $this->addSql('ALTER TABLE chat_history CHANGE id id INT NOT NULL, CHANGE id_patient id_patient INT DEFAULT NULL, CHANGE content content LONGTEXT NOT NULL, CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE chat_history RENAME INDEX id_patient TO IDX_6BB4BC22C4477E9B');
        $this->addSql('ALTER TABLE formation CHANGE id id INT NOT NULL, CHANGE description description LONGTEXT NOT NULL, CHANGE video_url video_url VARCHAR(255) NOT NULL, CHANGE category category VARCHAR(100) NOT NULL, CHANGE coach_id coach_id INT NOT NULL');
        $this->addSql('ALTER TABLE messages CHANGE id_message id_message INT NOT NULL, CHANGE id_expediteur id_expediteur INT DEFAULT NULL, CHANGE id_destinataire id_destinataire INT DEFAULT NULL, CHANGE contenu contenu LONGTEXT NOT NULL, CHANGE date_envoi date_envoi DATETIME NOT NULL, CHANGE lu lu TINYINT NOT NULL, CHANGE modifie modifie TINYINT NOT NULL, CHANGE type type VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE messages RENAME INDEX id_expediteur TO IDX_DB021E96E2E4F59');
        $this->addSql('ALTER TABLE messages RENAME INDEX id_destinataire TO IDX_DB021E96DD688AE0');
        $this->addSql('DROP INDEX user_id ON participation');
        $this->addSql('ALTER TABLE participation CHANGE id id INT NOT NULL, CHANGE user_id user_id INT DEFAULT NULL, CHANGE formation_id formation_id INT DEFAULT NULL, CHANGE date_inscription date_inscription DATETIME NOT NULL');
        $this->addSql('ALTER TABLE participation RENAME INDEX formation_id TO IDX_AB55E24F5200282E');
        $this->addSql('ALTER TABLE question CHANGE id id INT NOT NULL, CHANGE quiz_id quiz_id INT DEFAULT NULL, CHANGE question_text question_text LONGTEXT NOT NULL, CHANGE points points INT NOT NULL');
        $this->addSql('ALTER TABLE question RENAME INDEX quiz_id TO IDX_B6F7494E853CD175');
        $this->addSql('ALTER TABLE quiz DROP INDEX formation_id, ADD INDEX IDX_A412FA925200282E (formation_id)');
        $this->addSql('ALTER TABLE quiz CHANGE id id INT NOT NULL, CHANGE formation_id formation_id INT DEFAULT NULL, CHANGE passing_score passing_score INT NOT NULL');
        $this->addSql('DROP INDEX idx_user_quiz ON quiz_result');
        $this->addSql('ALTER TABLE quiz_result CHANGE id id INT NOT NULL, CHANGE quiz_id quiz_id INT DEFAULT NULL, CHANGE score score INT NOT NULL, CHANGE total_points total_points INT NOT NULL, CHANGE passed passed TINYINT NOT NULL, CHANGE completed_at completed_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE quiz_result RENAME INDEX quiz_id TO IDX_FE2E314A853CD175');
        $this->addSql('ALTER TABLE rapport CHANGE id_rapport id_rapport INT NOT NULL, CHANGE id_patient id_patient INT DEFAULT NULL, CHANGE id_coach id_coach INT DEFAULT NULL, CHANGE contenu contenu LONGTEXT NOT NULL, CHANGE recommandations recommandations LONGTEXT NOT NULL, CHANGE nb_seances nb_seances INT NOT NULL, CHANGE score_humeur score_humeur DOUBLE PRECISION NOT NULL, CHANGE periode periode VARCHAR(255) NOT NULL, CHANGE date_creation date_creation DATETIME NOT NULL, CHANGE fichier_pdf fichier_pdf VARCHAR(512) NOT NULL');
        $this->addSql('ALTER TABLE rapport RENAME INDEX id_patient TO IDX_BE34A09CC4477E9B');
        $this->addSql('ALTER TABLE rapport RENAME INDEX id_coach TO IDX_BE34A09CD1DC2CFC');
        $this->addSql('ALTER TABLE reponse CHANGE id id INT NOT NULL, CHANGE question_id question_id INT DEFAULT NULL, CHANGE is_correct is_correct TINYINT NOT NULL');
        $this->addSql('ALTER TABLE reponse RENAME INDEX question_id TO IDX_5FB6DEC71E27F6BF');
        $this->addSql('DROP INDEX email ON user');
        $this->addSql('ALTER TABLE user CHANGE id_user id_user INT NOT NULL, CHANGE role role VARCHAR(255) NOT NULL, CHANGE num_tel num_tel VARCHAR(20) NOT NULL, CHANGE photo photo VARCHAR(500) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE messenger_messages');
        $this->addSql('ALTER TABLE calls CHANGE id_call id_call INT AUTO_INCREMENT NOT NULL, CHANGE status status ENUM(\'RINGING\', \'ACCEPTED\', \'REJECTED\', \'ENDED\', \'MISSED\') DEFAULT \'RINGING\', CHANGE date_appel date_appel DATETIME DEFAULT CURRENT_TIMESTAMP, CHANGE duree_secondes duree_secondes INT DEFAULT 0, CHANGE caller_ip caller_ip VARCHAR(50) DEFAULT NULL, CHANGE caller_port caller_port INT DEFAULT 0, CHANGE id_caller id_caller INT NOT NULL, CHANGE id_receiver id_receiver INT NOT NULL');
        $this->addSql('ALTER TABLE calls RENAME INDEX idx_daa35c8f2b7bc24a TO id_caller');
        $this->addSql('ALTER TABLE calls RENAME INDEX idx_daa35c8f6d636003 TO id_receiver');
        $this->addSql('ALTER TABLE chat_history CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE content content TEXT NOT NULL, CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP, CHANGE id_patient id_patient INT NOT NULL');
        $this->addSql('CREATE INDEX session_id ON chat_history (session_id)');
        $this->addSql('ALTER TABLE chat_history RENAME INDEX idx_6bb4bc22c4477e9b TO id_patient');
        $this->addSql('ALTER TABLE formation CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE description description TEXT DEFAULT NULL, CHANGE video_url video_url VARCHAR(255) DEFAULT NULL, CHANGE category category VARCHAR(100) DEFAULT NULL, CHANGE coach_id coach_id INT DEFAULT 0');
        $this->addSql('ALTER TABLE messages CHANGE id_message id_message INT AUTO_INCREMENT NOT NULL, CHANGE contenu contenu TEXT NOT NULL, CHANGE date_envoi date_envoi DATETIME DEFAULT CURRENT_TIMESTAMP, CHANGE lu lu TINYINT DEFAULT 0, CHANGE modifie modifie TINYINT DEFAULT 0, CHANGE type type ENUM(\'TEXT\', \'CALL_IN\', \'CALL_OUT\', \'CALL_MISSED\') DEFAULT \'TEXT\', CHANGE id_expediteur id_expediteur INT NOT NULL, CHANGE id_destinataire id_destinataire INT NOT NULL');
        $this->addSql('ALTER TABLE messages RENAME INDEX idx_db021e96dd688ae0 TO id_destinataire');
        $this->addSql('ALTER TABLE messages RENAME INDEX idx_db021e96e2e4f59 TO id_expediteur');
        $this->addSql('ALTER TABLE participation CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE date_inscription date_inscription DATETIME DEFAULT CURRENT_TIMESTAMP, CHANGE user_id user_id INT NOT NULL, CHANGE formation_id formation_id INT NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX user_id ON participation (user_id, formation_id)');
        $this->addSql('ALTER TABLE participation RENAME INDEX idx_ab55e24f5200282e TO formation_id');
        $this->addSql('ALTER TABLE question CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE question_text question_text TEXT NOT NULL, CHANGE points points INT DEFAULT 5, CHANGE quiz_id quiz_id INT NOT NULL');
        $this->addSql('ALTER TABLE question RENAME INDEX idx_b6f7494e853cd175 TO quiz_id');
        $this->addSql('ALTER TABLE quiz DROP INDEX IDX_A412FA925200282E, ADD UNIQUE INDEX formation_id (formation_id)');
        $this->addSql('ALTER TABLE quiz CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE passing_score passing_score INT DEFAULT 70, CHANGE formation_id formation_id INT NOT NULL');
        $this->addSql('ALTER TABLE quiz_result CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE score score INT DEFAULT 0 NOT NULL, CHANGE total_points total_points INT DEFAULT 0 NOT NULL, CHANGE passed passed TINYINT DEFAULT 0 NOT NULL, CHANGE completed_at completed_at DATETIME DEFAULT CURRENT_TIMESTAMP, CHANGE quiz_id quiz_id INT NOT NULL');
        $this->addSql('CREATE INDEX idx_user_quiz ON quiz_result (user_id, quiz_id)');
        $this->addSql('ALTER TABLE quiz_result RENAME INDEX idx_fe2e314a853cd175 TO quiz_id');
        $this->addSql('ALTER TABLE rapport CHANGE id_rapport id_rapport INT AUTO_INCREMENT NOT NULL, CHANGE contenu contenu TEXT DEFAULT NULL, CHANGE recommandations recommandations TEXT DEFAULT NULL, CHANGE nb_seances nb_seances INT DEFAULT 1, CHANGE score_humeur score_humeur DOUBLE PRECISION DEFAULT \'5\', CHANGE periode periode VARCHAR(255) DEFAULT NULL, CHANGE date_creation date_creation DATETIME DEFAULT CURRENT_TIMESTAMP, CHANGE fichier_pdf fichier_pdf VARCHAR(512) DEFAULT NULL, CHANGE id_patient id_patient INT NOT NULL, CHANGE id_coach id_coach INT NOT NULL');
        $this->addSql('ALTER TABLE rapport RENAME INDEX idx_be34a09cd1dc2cfc TO id_coach');
        $this->addSql('ALTER TABLE rapport RENAME INDEX idx_be34a09cc4477e9b TO id_patient');
        $this->addSql('ALTER TABLE reponse CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE is_correct is_correct TINYINT DEFAULT 0, CHANGE question_id question_id INT NOT NULL');
        $this->addSql('ALTER TABLE reponse RENAME INDEX idx_5fb6dec71e27f6bf TO question_id');
        $this->addSql('ALTER TABLE user CHANGE id_user id_user INT AUTO_INCREMENT NOT NULL, CHANGE role role ENUM(\'Patient\', \'Admin\', \'Coach\') NOT NULL, CHANGE num_tel num_tel VARCHAR(20) DEFAULT NULL, CHANGE photo photo VARCHAR(500) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX email ON user (email)');
    }
}
