<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260605132511 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add session note and attached documents';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE teaching_document (id UUID NOT NULL, name VARCHAR(255) NOT NULL, size INT NOT NULL, content_type VARCHAR(150) NOT NULL, session_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_56005A4C613FECDF ON teaching_document (session_id)');
        $this->addSql('ALTER TABLE teaching_document ADD CONSTRAINT FK_56005A4C613FECDF FOREIGN KEY (session_id) REFERENCES teaching_session (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE teaching_session ADD note TEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE teaching_document DROP CONSTRAINT FK_56005A4C613FECDF');
        $this->addSql('DROP TABLE teaching_document');
        $this->addSql('ALTER TABLE teaching_session DROP note');
    }
}
