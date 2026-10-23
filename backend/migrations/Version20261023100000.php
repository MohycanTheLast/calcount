<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261023100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Externí přihlášení: tabulka user_identity (Google, Apple, …), heslo uživatele může být prázdné';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE user_identity (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, provider VARCHAR(20) NOT NULL, provider_user_id VARCHAR(255) NOT NULL, email VARCHAR(180) DEFAULT NULL, linked_at DATETIME NOT NULL, INDEX IDX_8A180DC4A76ED395 (user_id), UNIQUE INDEX UNIQ_PROVIDER_USER (provider, provider_user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE user_identity ADD CONSTRAINT FK_8A180DC4A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user CHANGE password password VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_identity DROP FOREIGN KEY FK_8A180DC4A76ED395');
        $this->addSql('DROP TABLE user_identity');
        $this->addSql('ALTER TABLE user CHANGE password password VARCHAR(255) NOT NULL');
    }
}
