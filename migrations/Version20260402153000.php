<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260402153000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add assigned_role on signalement and GPS coordinates on bus_stop';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement ADD assigned_role VARCHAR(32) DEFAULT NULL');
        $this->addSql('ALTER TABLE bus_stop ADD latitude DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE bus_stop ADD longitude DOUBLE PRECISION DEFAULT NULL');

        $this->addSql("UPDATE bus_stop SET latitude = 48.9006, longitude = 2.2933 WHERE id = 'gabriel-peri-metro'");
        $this->addSql("UPDATE bus_stop SET latitude = 48.8934, longitude = 2.2978 WHERE id = 'place-voltaire'");
        $this->addSql("UPDATE bus_stop SET latitude = 48.9213, longitude = 2.2966 WHERE id = 'marche-de-gennevilliers'");
        $this->addSql("UPDATE bus_stop SET latitude = 48.9178, longitude = 2.2944 WHERE id = 'cite-jardins'");
        $this->addSql("UPDATE bus_stop SET latitude = 48.9241, longitude = 2.2989 WHERE id = 'gresilons-felix-eboue'");
        $this->addSql("UPDATE bus_stop SET latitude = 48.9112, longitude = 2.2855 WHERE id = 'tour-dasnieres-laurent-cely'");
        $this->addSql("UPDATE bus_stop SET latitude = 48.9156, longitude = 2.2901 WHERE id = 'pierre-curie'");
        $this->addSql("UPDATE bus_stop SET latitude = 48.9248, longitude = 2.3012 WHERE id = 'les-gresilons'");
        $this->addSql("UPDATE bus_stop SET latitude = 48.9267, longitude = 2.3045 WHERE id = 'caboeufs'");
        $this->addSql("UPDATE bus_stop SET latitude = 48.9289, longitude = 2.3089 WHERE id = 'quatre-chemins'");
        $this->addSql("UPDATE bus_stop SET latitude = 48.9301, longitude = 2.3123 WHERE id = 'bongarde'");
        $this->addSql("UPDATE bus_stop SET latitude = 48.9134, longitude = 2.3067 WHERE id = 'lycee-petiet-cc'");
        $this->addSql("UPDATE bus_stop SET latitude = 48.9078, longitude = 2.3034 WHERE id = 'maurice-ravel'");
        $this->addSql("UPDATE bus_stop SET latitude = 48.9056, longitude = 2.3056 WHERE id = 'pointet'");
        $this->addSql("UPDATE bus_stop SET latitude = 48.9034, longitude = 2.3078 WHERE id = 'la-noue'");
        $this->addSql("UPDATE bus_stop SET latitude = 48.9012, longitude = 2.3101 WHERE id = 'gerard-philipe'");
        $this->addSql("UPDATE bus_stop SET latitude = 48.8989, longitude = 2.3123 WHERE id = 'stade'");
        $this->addSql("UPDATE bus_stop SET latitude = 48.8967, longitude = 2.3145 WHERE id = 'place-paul-herbe'");
        $this->addSql("UPDATE bus_stop SET latitude = 48.8945, longitude = 2.3167 WHERE id = 'saint-exupery'");
        $this->addSql("UPDATE bus_stop SET latitude = 48.8923, longitude = 2.3189 WHERE id = 'parc-departemental'");
        $this->addSql("UPDATE bus_stop SET latitude = 48.9312, longitude = 2.3212 WHERE id = 'zone-industrielle-nord'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE bus_stop DROP latitude');
        $this->addSql('ALTER TABLE bus_stop DROP longitude');
        $this->addSql('ALTER TABLE signalement DROP assigned_role');
    }
}
