<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261023120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Datum dne u záznamu (diet_record.entry_date) – doplněno z created_at; aktivní kalorie podle zdroje (activity_energy) a nastavení user_settings.activity_source';
    }

    public function up(Schema $schema): void
    {
        // F1: entry_date – nejdřív nullable, doplnit ze stávajícího created_at, pak NOT NULL
        $this->addSql('ALTER TABLE diet_record ADD entry_date DATE DEFAULT NULL');
        $this->addSql('UPDATE diet_record SET entry_date = DATE(COALESCE(created_at, NOW()))');
        $this->addSql('ALTER TABLE diet_record CHANGE entry_date entry_date DATE NOT NULL');
        $this->addSql('CREATE INDEX IDX_DIET_RECORD_USER_DATE ON diet_record (user_id, entry_date)');

        // F3: aktivní kalorie za den podle zdroje
        $this->addSql('CREATE TABLE activity_energy (id INT AUTO_INCREMENT NOT NULL, date DATE NOT NULL, source VARCHAR(20) NOT NULL, active_kcal INT NOT NULL, external_id VARCHAR(255) DEFAULT NULL, synced_at DATETIME NOT NULL, user_id INT NOT NULL, INDEX IDX_C5679991A76ED395 (user_id), UNIQUE INDEX UNIQ_ACTIVITY_USER_DATE_SOURCE (user_id, date, source), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE activity_energy ADD CONSTRAINT FK_C5679991A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql("ALTER TABLE user_settings ADD activity_source VARCHAR(20) DEFAULT 'static' NOT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_settings DROP activity_source');
        $this->addSql('ALTER TABLE activity_energy DROP FOREIGN KEY FK_C5679991A76ED395');
        $this->addSql('DROP TABLE activity_energy');
        $this->addSql('DROP INDEX IDX_DIET_RECORD_USER_DATE ON diet_record');
        $this->addSql('ALTER TABLE diet_record DROP entry_date');
    }
}
