<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260402102000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add suggestion field on signalement and create messenger_messages queue table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement ADD suggestion TEXT DEFAULT NULL');

        $this->addSql('CREATE TABLE IF NOT EXISTS messenger_messages (id BIGSERIAL NOT NULL, body TEXT NOT NULL, headers TEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, available_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, delivered_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_75EA56E0FB7336F0 ON messenger_messages (queue_name)');
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_75EA56E0E3BD61CE ON messenger_messages (available_at)');
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_75EA56E016BA31DB ON messenger_messages (delivered_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS IDX_75EA56E016BA31DB');
        $this->addSql('DROP INDEX IF EXISTS IDX_75EA56E0E3BD61CE');
        $this->addSql('DROP INDEX IF EXISTS IDX_75EA56E0FB7336F0');
        $this->addSql('DROP TABLE IF EXISTS messenger_messages');

        $this->addSql('ALTER TABLE signalement DROP suggestion');
    }
}
