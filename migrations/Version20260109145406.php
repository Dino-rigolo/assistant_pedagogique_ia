<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260109145406 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__session AS SELECT id, index_number, title, objectives, contents, activities, resources, done, actual_notes, planned_duration_minutes FROM session');
        $this->addSql('DROP TABLE session');
        $this->addSql('CREATE TABLE session (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, index_number INTEGER NOT NULL, title VARCHAR(255) NOT NULL, objectives CLOB DEFAULT NULL, contents CLOB DEFAULT NULL, activities CLOB DEFAULT NULL, resources CLOB DEFAULT NULL, done BOOLEAN NOT NULL, actual_notes CLOB DEFAULT NULL, planned_duration_minutes INTEGER NOT NULL, course_plan_id INTEGER NOT NULL, CONSTRAINT FK_D044D5D4E05A0777 FOREIGN KEY (course_plan_id) REFERENCES course_plan (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO session (id, index_number, title, objectives, contents, activities, resources, done, actual_notes, planned_duration_minutes) SELECT id, index_number, title, objectives, contents, activities, resources, done, actual_notes, planned_duration_minutes FROM __temp__session');
        $this->addSql('DROP TABLE __temp__session');
        $this->addSql('CREATE INDEX IDX_D044D5D4E05A0777 ON session (course_plan_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__session AS SELECT id, index_number, title, objectives, contents, activities, resources, done, actual_notes, planned_duration_minutes FROM session');
        $this->addSql('DROP TABLE session');
        $this->addSql('CREATE TABLE session (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, index_number INTEGER NOT NULL, title VARCHAR(255) NOT NULL, objectives CLOB DEFAULT NULL, contents CLOB DEFAULT NULL, activities CLOB DEFAULT NULL, resources CLOB DEFAULT NULL, done BOOLEAN NOT NULL, actual_notes CLOB DEFAULT NULL, planned_duration_minutes INTEGER NOT NULL)');
        $this->addSql('INSERT INTO session (id, index_number, title, objectives, contents, activities, resources, done, actual_notes, planned_duration_minutes) SELECT id, index_number, title, objectives, contents, activities, resources, done, actual_notes, planned_duration_minutes FROM __temp__session');
        $this->addSql('DROP TABLE __temp__session');
    }
}
