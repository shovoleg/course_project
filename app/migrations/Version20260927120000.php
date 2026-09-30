<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260927120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add email verification to app_user';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE app_user ADD is_verified TINYINT(1) NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE app_user ADD verification_token VARCHAR(64) DEFAULT NULL');
        $this->addSql('ALTER TABLE app_user ADD verification_expires_at DATETIME DEFAULT NULL');
        $this->addSql('UPDATE app_user SET is_verified = 1');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE app_user DROP is_verified');
        $this->addSql('ALTER TABLE app_user DROP verification_token');
        $this->addSql('ALTER TABLE app_user DROP verification_expires_at');
    }
}
