<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260216162723 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE diet_record ADD user_id INT NOT NULL');
        $this->addSql('ALTER TABLE diet_record ADD CONSTRAINT FK_67FD8701A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_67FD8701A76ED395 ON diet_record (user_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE diet_record DROP FOREIGN KEY FK_67FD8701A76ED395');
        $this->addSql('DROP INDEX IDX_67FD8701A76ED395 ON diet_record');
        $this->addSql('ALTER TABLE diet_record DROP user_id');
    }
}
