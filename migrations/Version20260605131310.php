<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260605131310 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add subject (required) and room (optional) to timetable slots';
    }

    public function up(Schema $schema): void
    {
        // Backfill existing rows with a temporary default, then drop it so the
        // column matches the entity mapping (no server-side default).
        $this->addSql("ALTER TABLE scheduling_timetable_slot ADD subject VARCHAR(80) NOT NULL DEFAULT 'Français'");
        $this->addSql('ALTER TABLE scheduling_timetable_slot ALTER COLUMN subject DROP DEFAULT');
        $this->addSql('ALTER TABLE scheduling_timetable_slot ADD room VARCHAR(40) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE scheduling_timetable_slot DROP subject');
        $this->addSql('ALTER TABLE scheduling_timetable_slot DROP room');
    }
}
