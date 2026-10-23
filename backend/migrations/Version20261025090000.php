<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261025090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Registrace: datum založení účtu, ověření e-mailu a souhlas s podmínkami (user)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD created_at DATETIME DEFAULT NULL, ADD email_verified_at DATETIME DEFAULT NULL, ADD terms_accepted_at DATETIME DEFAULT NULL, ADD terms_version VARCHAR(20) DEFAULT NULL');
        // Dosavadní účty zakládal správce ručně – e-mail bereme jako ověřený (jinak by se po zapnutí ověřování nepřihlásily)
        $this->addSql('UPDATE user SET email_verified_at = NOW()');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP created_at, DROP email_verified_at, DROP terms_accepted_at, DROP terms_version');
    }
}
