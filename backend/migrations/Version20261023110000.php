<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261023110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'user_settings.user_id unikátní – vztah User ↔ UserSettings je 1:1 (sladění DB s mapováním)';
    }

    public function up(Schema $schema): void
    {
        // Selže, pokud má některý uživatel víc řádků nastavení – pak je nejdřív potřeba duplicity odstranit.
        $this->addSql('ALTER TABLE user_settings DROP INDEX IDX_5C844C5A76ED395, ADD UNIQUE INDEX UNIQ_5C844C5A76ED395 (user_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_settings DROP INDEX UNIQ_5C844C5A76ED395, ADD INDEX IDX_5C844C5A76ED395 (user_id)');
    }
}
