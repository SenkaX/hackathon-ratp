<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260331143000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create bus_stop, signalement, and motif_gravite tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE bus_stop (id VARCHAR(120) NOT NULL, label VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE motif_gravite (motif VARCHAR(64) NOT NULL, gravite INT NOT NULL, PRIMARY KEY(motif))');
            $this->addSql("CREATE TABLE signalement (id VARCHAR(36) NOT NULL, stop_id VARCHAR(120) DEFAULT NULL, email VARCHAR(255) NOT NULL, motif VARCHAR(64) NOT NULL, details TEXT NOT NULL, incident_date DATE DEFAULT NULL, submitted_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, is_test BOOLEAN DEFAULT FALSE NOT NULL, status VARCHAR(32) DEFAULT 'en_cours' NOT NULL, access_token VARCHAR(64) NOT NULL, PRIMARY KEY(id))");
        $this->addSql('CREATE INDEX IDX_SIGNAL_STOP_ID ON signalement (stop_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_SIGNAL_ACCESS_TOKEN ON signalement (access_token)');
        $this->addSql('ALTER TABLE signalement ADD CONSTRAINT FK_SIGNAL_STOP FOREIGN KEY (stop_id) REFERENCES bus_stop (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement DROP CONSTRAINT FK_SIGNAL_STOP');
        $this->addSql('DROP TABLE signalement');
        $this->addSql('DROP TABLE motif_gravite');
        $this->addSql('DROP TABLE bus_stop');
    }
}
