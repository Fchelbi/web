<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260409101500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute des psychologues de demonstration pour la liste patient.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("INSERT INTO `user` (`name`) SELECT * FROM (SELECT 'Dr Sarah Martin') AS tmp WHERE NOT EXISTS (SELECT 1 FROM `user` WHERE `name` = 'Dr Sarah Martin')");
        $this->addSql("INSERT INTO `user` (`name`) SELECT * FROM (SELECT 'Dr Youssef Ben Salem') AS tmp WHERE NOT EXISTS (SELECT 1 FROM `user` WHERE `name` = 'Dr Youssef Ben Salem')");
        $this->addSql("INSERT INTO `user` (`name`) SELECT * FROM (SELECT 'Dr Lina Haddad') AS tmp WHERE NOT EXISTS (SELECT 1 FROM `user` WHERE `name` = 'Dr Lina Haddad')");

        $this->addSql("
            INSERT INTO psychologue (user_id, specialite, telephone, description)
            SELECT u.id, 'Gestion du stress', '221001122', 'Accompagnement des adultes pour la gestion du stress, de l anxiete et des difficultes du quotidien.'
            FROM `user` u
            WHERE u.name = 'Dr Sarah Martin'
              AND NOT EXISTS (
                SELECT 1
                FROM psychologue p
                WHERE p.user_id = u.id
              )
        ");

        $this->addSql("
            INSERT INTO psychologue (user_id, specialite, telephone, description)
            SELECT u.id, 'Therapie de couple', '221003344', 'Suivi des couples et des familles avec une approche bienveillante et orientee communication.'
            FROM `user` u
            WHERE u.name = 'Dr Youssef Ben Salem'
              AND NOT EXISTS (
                SELECT 1
                FROM psychologue p
                WHERE p.user_id = u.id
              )
        ");

        $this->addSql("
            INSERT INTO psychologue (user_id, specialite, telephone, description)
            SELECT u.id, 'Psychologie clinique', '221005566', 'Prise en charge des adolescents et des adultes avec une ecoute active et un suivi personnalise.'
            FROM `user` u
            WHERE u.name = 'Dr Lina Haddad'
              AND NOT EXISTS (
                SELECT 1
                FROM psychologue p
                WHERE p.user_id = u.id
              )
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            DELETE p FROM psychologue p
            INNER JOIN `user` u ON u.id = p.user_id
            WHERE u.name IN ('Dr Sarah Martin', 'Dr Youssef Ben Salem', 'Dr Lina Haddad')
        ");

        $this->addSql("DELETE FROM `user` WHERE `name` IN ('Dr Sarah Martin', 'Dr Youssef Ben Salem', 'Dr Lina Haddad')");
    }
}
