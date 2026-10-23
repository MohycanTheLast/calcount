<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261024090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Texty záznamů (diet_record.content) zpět z HTML entit – dřív se ukládaly přes htmlspecialchars';
    }

    public function up(Schema $schema): void
    {
        // htmlspecialchars(ENT_QUOTES) kódoval jen & " ' < > – stačí projít řádky s „&“
        $rows = $this->connection->fetchAllAssociative("SELECT id, content FROM diet_record WHERE content LIKE '%&%'");

        foreach ($rows as $row) {
            $decoded = htmlspecialchars_decode($row['content'], ENT_QUOTES);
            if ($decoded !== $row['content']) {
                $this->addSql('UPDATE diet_record SET content = ? WHERE id = ?', [$decoded, $row['id']]);
            }
        }
    }

    public function down(Schema $schema): void
    {
        $rows = $this->connection->fetchAllAssociative('SELECT id, content FROM diet_record');

        foreach ($rows as $row) {
            $encoded = htmlspecialchars($row['content'], ENT_QUOTES, 'UTF-8');
            if ($encoded !== $row['content']) {
                $this->addSql('UPDATE diet_record SET content = ? WHERE id = ?', [mb_substr($encoded, 0, 255), $row['id']]);
            }
        }
    }
}
