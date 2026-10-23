<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261025110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Úvodní průvodce aplikací: user.onboarding_completed_at';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD onboarding_completed_at DATETIME DEFAULT NULL');
        // Dosavadní uživatelé aplikaci znají – průvodce uvidí jen nové účty (znovu ho jde spustit z Nastavení)
        $this->addSql('UPDATE user SET onboarding_completed_at = NOW()');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP onboarding_completed_at');
    }
}
