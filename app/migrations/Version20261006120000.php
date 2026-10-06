<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261006120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add api_token to job_position';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE job_position ADD api_token VARCHAR(64) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX uniq_position_api_token ON job_position (api_token)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX uniq_position_api_token ON job_position');
        $this->addSql('ALTER TABLE job_position DROP api_token');
    }
}
