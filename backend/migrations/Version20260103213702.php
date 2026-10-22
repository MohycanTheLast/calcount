<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260103213702 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE burned_record (id INT AUTO_INCREMENT NOT NULL, note VARCHAR(255) DEFAULT NULL, kcal INT NOT NULL, update_at DATETIME DEFAULT NULL, created_at DATETIME DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE diet_record CHANGE kcal kcal INT NOT NULL, CHANGE water_in_ml water_in_ml INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE burned_record');
        $this->addSql('ALTER TABLE diet_record CHANGE kcal kcal DOUBLE PRECISION NOT NULL, CHANGE water_in_ml water_in_ml VARCHAR(255) NOT NULL');
    }
}
