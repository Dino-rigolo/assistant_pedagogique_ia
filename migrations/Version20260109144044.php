<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260109144044 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE course_plan (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, title VARCHAR(255) NOT NULL, general_plan CLOB NOT NULL, evaluation_criteria CLOB DEFAULT NULL, nb_sessions_planned INTEGER NOT NULL, expected_total_hours INTEGER NOT NULL, created_at DATETIME NOT NULL, syllabus_id INTEGER NOT NULL, owner_id INTEGER NOT NULL, CONSTRAINT FK_15F8867B824D79E7 FOREIGN KEY (syllabus_id) REFERENCES syllabus (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_15F8867B7E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_15F8867B824D79E7 ON course_plan (syllabus_id)');
        $this->addSql('CREATE INDEX IDX_15F8867B7E3C61F9 ON course_plan (owner_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE course_plan');
    }
}
