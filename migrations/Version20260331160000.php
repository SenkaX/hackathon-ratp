<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260331160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Change signalement.incident_date from DATE to TIMESTAMP';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement ALTER incident_date TYPE TIMESTAMP(0) WITHOUT TIME ZONE USING incident_date::timestamp(0) without time zone');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement ALTER incident_date TYPE DATE USING incident_date::date');
    }
}
