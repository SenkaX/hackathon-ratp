<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260401100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Legacy placeholder migration kept for Supabase migration history consistency.';
    }

    public function up(Schema $schema): void
    {
        // No-op: migration already executed historically on the target database.
    }

    public function down(Schema $schema): void
    {
        // No-op.
    }
}
