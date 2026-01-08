<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260108195601 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE administrateur CHANGE roles roles JSON NOT NULL, CHANGE telephone telephone VARCHAR(20) DEFAULT NULL, CHANGE dernier_connexion dernier_connexion DATETIME DEFAULT NULL, CHANGE departement departement VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE avion CHANGE immatriculation immatriculation VARCHAR(50) DEFAULT NULL, CHANGE derniere_maintenance derniere_maintenance DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE categorie_avion CHANGE compagnie compagnie VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE client CHANGE telephone telephone VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE passager CHANGE besoins_speciaux besoins_speciaux VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE utilisateur CHANGE roles roles JSON NOT NULL, CHANGE date_creation date_creation DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE vol CHANGE escale escale VARCHAR(255) DEFAULT NULL, CHANGE statut statut VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE messenger_messages CHANGE delivered_at delivered_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE administrateur CHANGE roles roles LONGTEXT NOT NULL COLLATE `utf8mb4_bin`, CHANGE telephone telephone VARCHAR(20) DEFAULT \'NULL\', CHANGE dernier_connexion dernier_connexion DATETIME DEFAULT \'NULL\', CHANGE departement departement VARCHAR(255) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE avion CHANGE immatriculation immatriculation VARCHAR(50) DEFAULT \'NULL\', CHANGE derniere_maintenance derniere_maintenance DATETIME DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE categorie_avion CHANGE compagnie compagnie VARCHAR(255) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE client CHANGE telephone telephone VARCHAR(20) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE messenger_messages CHANGE delivered_at delivered_at DATETIME DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE passager CHANGE besoins_speciaux besoins_speciaux VARCHAR(255) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE utilisateur CHANGE roles roles LONGTEXT NOT NULL COLLATE `utf8mb4_bin`, CHANGE date_creation date_creation DATETIME DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE vol CHANGE escale escale VARCHAR(255) DEFAULT \'NULL\', CHANGE statut statut VARCHAR(50) DEFAULT \'NULL\'');
    }
}
