<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260401110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add missing statuses (en_cours, resolu) to signalement table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE signalement ADD CONSTRAINT check_status CHECK (status IN ('nouveau', 'en_attente_validation', 'valide', 'en_cours', 'sans_suite', 'escalade_juridique', 'resolu', 'traite', 'clos'))");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement DROP CONSTRAINT check_status');
    }
}
