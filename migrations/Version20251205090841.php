<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251205090841 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE administrateur (id INT AUTO_INCREMENT NOT NULL, matricule VARCHAR(255) NOT NULL, niveau_acces INT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE ticket (id INT AUTO_INCREMENT NOT NULL, id_ticket INT NOT NULL, numero VARCHAR(255) NOT NULL, date_creation DATE NOT NULL, pdf_path VARCHAR(255) NOT NULL, reservation_id INT NOT NULL, passager_id INT DEFAULT NULL, vol_id INT DEFAULT NULL, INDEX IDX_97A0ADA3B83297E7 (reservation_id), INDEX IDX_97A0ADA371A51189 (passager_id), INDEX IDX_97A0ADA39F2BFB7A (vol_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE ticket ADD CONSTRAINT FK_97A0ADA3B83297E7 FOREIGN KEY (reservation_id) REFERENCES reservation (id)');
        $this->addSql('ALTER TABLE ticket ADD CONSTRAINT FK_97A0ADA371A51189 FOREIGN KEY (passager_id) REFERENCES passager (id)');
        $this->addSql('ALTER TABLE ticket ADD CONSTRAINT FK_97A0ADA39F2BFB7A FOREIGN KEY (vol_id) REFERENCES vol (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ticket DROP FOREIGN KEY FK_97A0ADA3B83297E7');
        $this->addSql('ALTER TABLE ticket DROP FOREIGN KEY FK_97A0ADA371A51189');
        $this->addSql('ALTER TABLE ticket DROP FOREIGN KEY FK_97A0ADA39F2BFB7A');
        $this->addSql('DROP TABLE administrateur');
        $this->addSql('DROP TABLE ticket');
    }
}
