<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260401100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add moderation review fields and internal confidence/priority scores on signalement';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement ADD priorite_score INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE signalement ADD confiance_score INT DEFAULT 100 NOT NULL');
        $this->addSql('ALTER TABLE signalement ADD reviewed_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE signalement ADD reviewed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE signalement ADD review_note TEXT DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_SIGNAL_REVIEWED_BY ON signalement (reviewed_by_id)');
        $this->addSql('ALTER TABLE signalement ADD CONSTRAINT FK_SIGNAL_REVIEWED_BY FOREIGN KEY (reviewed_by_id) REFERENCES "user" (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql("UPDATE signalement SET status = 'en_attente_validation' WHERE status = 'en_cours'");
        $this->addSql("ALTER TABLE signalement ALTER status SET DEFAULT 'en_attente_validation'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement DROP CONSTRAINT FK_SIGNAL_REVIEWED_BY');
        $this->addSql('DROP INDEX IDX_SIGNAL_REVIEWED_BY');
        $this->addSql('ALTER TABLE signalement DROP reviewed_by_id');
        $this->addSql('ALTER TABLE signalement DROP reviewed_at');
        $this->addSql('ALTER TABLE signalement DROP review_note');
        $this->addSql('ALTER TABLE signalement DROP priorite_score');
        $this->addSql('ALTER TABLE signalement DROP confiance_score');

        $this->addSql("UPDATE signalement SET status = 'en_cours' WHERE status = 'en_attente_validation'");
        $this->addSql("ALTER TABLE signalement ALTER status SET DEFAULT 'en_cours'");
    }
}
