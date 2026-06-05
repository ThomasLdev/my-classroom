<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260605060245 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE scheduling_classroom (id UUID NOT NULL, name VARCHAR(120) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE scheduling_timetable_slot (id UUID NOT NULL, day_of_week SMALLINT NOT NULL, start_minute INT NOT NULL, end_minute INT NOT NULL, valid_from DATE DEFAULT NULL, valid_to DATE DEFAULT NULL, classroom_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_C41804026278D5A8 ON scheduling_timetable_slot (classroom_id)');
        $this->addSql('ALTER TABLE scheduling_timetable_slot ADD CONSTRAINT FK_C41804026278D5A8 FOREIGN KEY (classroom_id) REFERENCES scheduling_classroom (id) ON DELETE CASCADE NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE scheduling_timetable_slot DROP CONSTRAINT FK_C41804026278D5A8');
        $this->addSql('DROP TABLE scheduling_classroom');
        $this->addSql('DROP TABLE scheduling_timetable_slot');
    }
}
