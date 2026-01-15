<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260115165956 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__syllabus AS SELECT id, title, raw_text, created_at, extracted_competences, owner_id FROM syllabus');
        $this->addSql('DROP TABLE syllabus');
        $this->addSql('CREATE TABLE syllabus (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, title VARCHAR(255) NOT NULL, raw_text CLOB NOT NULL, created_at DATETIME NOT NULL, extracted_competences CLOB DEFAULT NULL, owner_id INTEGER DEFAULT NULL, CONSTRAINT FK_4E74AB927E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO syllabus (id, title, raw_text, created_at, extracted_competences, owner_id) SELECT id, title, raw_text, created_at, extracted_competences, owner_id FROM __temp__syllabus');
        $this->addSql('DROP TABLE __temp__syllabus');
        $this->addSql('CREATE INDEX IDX_4E74AB927E3C61F9 ON syllabus (owner_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__syllabus AS SELECT id, title, raw_text, created_at, extracted_competences, owner_id FROM syllabus');
        $this->addSql('DROP TABLE syllabus');
        $this->addSql('CREATE TABLE syllabus (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, title VARCHAR(255) NOT NULL, raw_text CLOB NOT NULL, created_at DATETIME NOT NULL, extracted_competences CLOB DEFAULT NULL, owner_id INTEGER NOT NULL, CONSTRAINT FK_4E74AB927E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO syllabus (id, title, raw_text, created_at, extracted_competences, owner_id) SELECT id, title, raw_text, created_at, extracted_competences, owner_id FROM __temp__syllabus');
        $this->addSql('DROP TABLE __temp__syllabus');
        $this->addSql('CREATE INDEX IDX_4E74AB927E3C61F9 ON syllabus (owner_id)');
    }
}
