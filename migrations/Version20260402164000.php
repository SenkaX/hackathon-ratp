<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260402164000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Backfill assigned_role on historical signalements according to motif gravite';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE signalement s SET assigned_role = CASE WHEN mg.gravite >= 4 THEN 'ROLE_RH' ELSE 'ROLE_MANAGER' END FROM motif_gravite mg WHERE s.assigned_role IS NULL AND s.motif = mg.motif");
        $this->addSql("UPDATE signalement SET assigned_role = 'ROLE_MANAGER' WHERE assigned_role IS NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('UPDATE signalement SET assigned_role = NULL');
    }
}
