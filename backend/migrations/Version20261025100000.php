<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261025100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Osobní API tokeny (personal_access_token) pro zápis aktivních kalorií z iOS Zkratek';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE personal_access_token (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, name VARCHAR(100) NOT NULL, token_hash VARCHAR(64) NOT NULL, display_prefix VARCHAR(20) NOT NULL, scopes JSON NOT NULL, created_at DATETIME NOT NULL, last_used_at DATETIME DEFAULT NULL, revoked_at DATETIME DEFAULT NULL, INDEX IDX_PAT_USER (user_id), UNIQUE INDEX UNIQ_PAT_TOKEN_HASH (token_hash), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE personal_access_token ADD CONSTRAINT FK_PAT_USER FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE personal_access_token DROP FOREIGN KEY FK_PAT_USER');
        $this->addSql('DROP TABLE personal_access_token');
    }
}
