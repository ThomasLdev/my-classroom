<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260605054132 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE teaching_activity (
              id UUID NOT NULL,
              title VARCHAR(255) NOT NULL,
              status VARCHAR(16) NOT NULL,
              position INT NOT NULL,
              carried_over_from UUID DEFAULT NULL,
              session_id UUID NOT NULL,
              PRIMARY KEY (id)
            )
        SQL);
        $this->addSql('CREATE INDEX IDX_221DD960613FECDF ON teaching_activity (session_id)');
        $this->addSql(<<<'SQL'
            CREATE TABLE teaching_session (
              id UUID NOT NULL,
              classroom_id UUID NOT NULL,
              slot_id UUID DEFAULT NULL,
              date DATE NOT NULL,
              start_minute INT NOT NULL,
              end_minute INT NOT NULL,
              closed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
              cancelled BOOLEAN DEFAULT false NOT NULL,
              PRIMARY KEY (id)
            )
        SQL);
        $this->addSql('CREATE UNIQUE INDEX uniq_session_slot_date ON teaching_session (slot_id, date)');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              teaching_activity
            ADD
              CONSTRAINT FK_221DD960613FECDF FOREIGN KEY (session_id) REFERENCES teaching_session (id) ON DELETE CASCADE NOT DEFERRABLE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE teaching_activity DROP CONSTRAINT FK_221DD960613FECDF');
        $this->addSql('DROP TABLE teaching_activity');
        $this->addSql('DROP TABLE teaching_session');
    }
}
