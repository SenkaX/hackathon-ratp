<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260401102000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Normalize legacy signalement status values to en_attente_validation';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE signalement SET status = 'en_attente_validation' WHERE status IN ('nouveau', 'en_cours')");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE signalement SET status = 'en_cours' WHERE status = 'en_attente_validation'");
    }
}
