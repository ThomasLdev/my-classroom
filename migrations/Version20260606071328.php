<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260606071328 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add session homework and its verified flag';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE teaching_session ADD homework TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE teaching_session ADD homework_checked BOOLEAN DEFAULT false NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE teaching_session DROP homework');
        $this->addSql('ALTER TABLE teaching_session DROP homework_checked');
    }
}
