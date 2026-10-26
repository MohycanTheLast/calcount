<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261026100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Typy účtů (user.account_type), makroživiny a kofein u záznamů (diet_record), hlídání pitného režimu, kofeinu a makroživin (user_settings)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE user ADD account_type VARCHAR(20) DEFAULT 'free' NOT NULL");
        // Dosavadní účty jsou jen testovací (provozovatel a známí) – dostanou všechno, nové registrace jsou free
        $this->addSql("UPDATE user SET account_type = 'superior'");

        $this->addSql('ALTER TABLE diet_record ADD protein_g INT DEFAULT NULL, ADD carbs_g INT DEFAULT NULL, ADD sugar_g INT DEFAULT NULL, ADD fat_g INT DEFAULT NULL, ADD caffeine_mg INT DEFAULT NULL');

        $this->addSql('ALTER TABLE user_settings ADD hydration_enabled TINYINT(1) DEFAULT 0 NOT NULL, ADD water_goal_ml INT DEFAULT NULL, ADD caffeine_enabled TINYINT(1) DEFAULT 0 NOT NULL, ADD caffeine_limit_mg INT DEFAULT NULL, ADD macros_enabled TINYINT(1) DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_settings DROP hydration_enabled, DROP water_goal_ml, DROP caffeine_enabled, DROP caffeine_limit_mg, DROP macros_enabled');
        $this->addSql('ALTER TABLE diet_record DROP protein_g, DROP carbs_g, DROP sugar_g, DROP fat_g, DROP caffeine_mg');
        $this->addSql('ALTER TABLE user DROP account_type');
    }
}
