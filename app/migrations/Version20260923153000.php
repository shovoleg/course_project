<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260923153000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Give optimistic lock columns a database default';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE app_user MODIFY version INT NOT NULL DEFAULT 1');
        $this->addSql('ALTER TABLE cv_attribute MODIFY version INT NOT NULL DEFAULT 1');
        $this->addSql('ALTER TABLE candidate_attribute_value MODIFY version INT NOT NULL DEFAULT 1');
        $this->addSql('ALTER TABLE project MODIFY version INT NOT NULL DEFAULT 1');
        $this->addSql('ALTER TABLE job_position MODIFY version INT NOT NULL DEFAULT 1');
        $this->addSql('ALTER TABLE cv MODIFY version INT NOT NULL DEFAULT 1');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE app_user MODIFY version INT NOT NULL');
        $this->addSql('ALTER TABLE cv_attribute MODIFY version INT NOT NULL');
        $this->addSql('ALTER TABLE candidate_attribute_value MODIFY version INT NOT NULL');
        $this->addSql('ALTER TABLE project MODIFY version INT NOT NULL');
        $this->addSql('ALTER TABLE job_position MODIFY version INT NOT NULL');
        $this->addSql('ALTER TABLE cv MODIFY version INT NOT NULL');
    }
}
