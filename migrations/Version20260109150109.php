<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260109150109 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE exercise (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, title VARCHAR(255) NOT NULL, instruction CLOB NOT NULL, difficulty VARCHAR(20) NOT NULL, expected_duration_minutes INTEGER NOT NULL, correction CLOB DEFAULT NULL, session_id INTEGER NOT NULL, CONSTRAINT FK_AEDAD51C613FECDF FOREIGN KEY (session_id) REFERENCES session (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_AEDAD51C613FECDF ON exercise (session_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE exercise');
    }
}
