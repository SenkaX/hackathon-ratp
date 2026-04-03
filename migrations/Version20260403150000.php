<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260403150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add human review fields for AI suggestion on signalement';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement ADD suggestion_validated BOOLEAN DEFAULT NULL');
        $this->addSql('ALTER TABLE signalement ADD suggestion_human_response TEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement DROP suggestion_human_response');
        $this->addSql('ALTER TABLE signalement DROP suggestion_validated');
    }
}
