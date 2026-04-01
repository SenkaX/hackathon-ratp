<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260401120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add secure ticket token hash/expiration columns and anonymization tracking for RGPD purge';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement ADD token_hash VARCHAR(64) DEFAULT NULL');
        $this->addSql('ALTER TABLE signalement ADD token_expires_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE signalement ADD anonymized_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');

        $rows = $this->connection->fetchAllAssociative('SELECT id, access_token FROM signalement WHERE access_token IS NOT NULL');
        foreach ($rows as $row) {
            $id = $this->connection->quote((string) $row['id']);
            $hash = $this->connection->quote(hash('sha256', (string) $row['access_token']));
            $this->addSql("UPDATE signalement SET token_hash = $hash, token_expires_at = submitted_at + INTERVAL '30 days' WHERE id = $id");
        }

        $this->addSql('CREATE UNIQUE INDEX UNIQ_SIGNAL_TOKEN_HASH ON signalement (token_hash)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_SIGNAL_TOKEN_HASH');
        $this->addSql('ALTER TABLE signalement DROP token_hash');
        $this->addSql('ALTER TABLE signalement DROP token_expires_at');
        $this->addSql('ALTER TABLE signalement DROP anonymized_at');
    }
}
