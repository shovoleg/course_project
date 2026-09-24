<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260922120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the CV platform schema';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE app_user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, password VARCHAR(255) DEFAULT NULL, roles JSON NOT NULL, blocked TINYINT(1) NOT NULL, locale VARCHAR(8) NOT NULL, theme VARCHAR(16) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, version INT NOT NULL DEFAULT 1, UNIQUE INDEX uniq_user_email (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE social_account (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, provider VARCHAR(32) NOT NULL, provider_user_id VARCHAR(255) NOT NULL, UNIQUE INDEX uniq_social_provider (provider, provider_user_id), INDEX IDX_social_user (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE attribute_category (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(64) NOT NULL, UNIQUE INDEX uniq_category_code (code), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE cv_attribute (id INT AUTO_INCREMENT NOT NULL, category_id INT NOT NULL, name VARCHAR(180) NOT NULL, description LONGTEXT DEFAULT NULL, type VARCHAR(32) NOT NULL, builtin TINYINT(1) NOT NULL, code VARCHAR(64) DEFAULT NULL, last_used_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, version INT NOT NULL DEFAULT 1, UNIQUE INDEX uniq_attribute_name (name), UNIQUE INDEX uniq_attribute_code (code), INDEX IDX_attribute_category (category_id), FULLTEXT INDEX ft_attribute (name, description), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE attribute_option (id INT AUTO_INCREMENT NOT NULL, attribute_id INT NOT NULL, label VARCHAR(180) NOT NULL, sort_order INT NOT NULL, INDEX IDX_option_attribute (attribute_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE candidate_attribute_value (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, attribute_id INT NOT NULL, string_value VARCHAR(255) DEFAULT NULL, text_value LONGTEXT DEFAULT NULL, numeric_value NUMERIC(12, 4) DEFAULT NULL, date_value DATE DEFAULT NULL, period_start DATE DEFAULT NULL, period_end DATE DEFAULT NULL, boolean_value TINYINT(1) DEFAULT NULL, image_url VARCHAR(1024) DEFAULT NULL, image_public_id VARCHAR(255) DEFAULT NULL, option_id INT DEFAULT NULL, updated_at DATETIME NOT NULL, version INT NOT NULL DEFAULT 1, UNIQUE INDEX uniq_value_user_attribute (user_id, attribute_id), INDEX IDX_value_user (user_id), INDEX IDX_value_attribute (attribute_id), INDEX IDX_value_option (option_id), FULLTEXT INDEX ft_value (string_value, text_value), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tag (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(64) NOT NULL, created_at DATETIME NOT NULL, UNIQUE INDEX uniq_tag_name (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE project (id INT AUTO_INCREMENT NOT NULL, owner_id INT NOT NULL, name VARCHAR(180) NOT NULL, period_start DATE DEFAULT NULL, period_end DATE DEFAULT NULL, description LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, version INT NOT NULL DEFAULT 1, INDEX IDX_project_owner (owner_id), FULLTEXT INDEX ft_project (name, description), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE project_tag (project_id INT NOT NULL, tag_id INT NOT NULL, INDEX IDX_project_tag_project (project_id), INDEX IDX_project_tag_tag (tag_id), PRIMARY KEY(project_id, tag_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE job_position (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(180) NOT NULL, short_description LONGTEXT NOT NULL, company VARCHAR(180) DEFAULT NULL, level VARCHAR(16) DEFAULT NULL, visibility VARCHAR(16) NOT NULL, max_projects INT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, version INT NOT NULL DEFAULT 1, INDEX idx_position_updated (updated_at), FULLTEXT INDEX ft_position (title, short_description, company), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE position_tag (position_id INT NOT NULL, tag_id INT NOT NULL, INDEX IDX_position_tag_position (position_id), INDEX IDX_position_tag_tag (tag_id), PRIMARY KEY(position_id, tag_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE position_attribute (id INT AUTO_INCREMENT NOT NULL, position_id INT NOT NULL, attribute_id INT NOT NULL, sort_order INT NOT NULL, UNIQUE INDEX uniq_position_attribute (position_id, attribute_id), INDEX IDX_position_attribute_position (position_id), INDEX IDX_position_attribute_attribute (attribute_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE position_access_rule (id INT AUTO_INCREMENT NOT NULL, position_id INT NOT NULL, attribute_id INT NOT NULL, operator VARCHAR(16) NOT NULL, comparison VARCHAR(255) DEFAULT NULL, INDEX IDX_rule_position (position_id), INDEX IDX_rule_attribute (attribute_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE cv (id INT AUTO_INCREMENT NOT NULL, owner_id INT NOT NULL, position_id INT NOT NULL, status VARCHAR(16) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, version INT NOT NULL DEFAULT 1, UNIQUE INDEX uniq_cv_owner_position (owner_id, position_id), INDEX idx_cv_created (created_at), INDEX IDX_cv_owner (owner_id), INDEX IDX_cv_position (position_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE cv_like (id INT AUTO_INCREMENT NOT NULL, cv_id INT NOT NULL, recruiter_id INT NOT NULL, created_at DATETIME NOT NULL, UNIQUE INDEX uniq_cv_like (cv_id, recruiter_id), INDEX IDX_like_cv (cv_id), INDEX IDX_like_recruiter (recruiter_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE discussion_post (id INT AUTO_INCREMENT NOT NULL, position_id INT NOT NULL, author_id INT NOT NULL, body LONGTEXT NOT NULL, created_at DATETIME NOT NULL, INDEX idx_post_position (position_id, id), INDEX IDX_post_author (author_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE social_account ADD CONSTRAINT FK_social_user FOREIGN KEY (user_id) REFERENCES app_user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE cv_attribute ADD CONSTRAINT FK_attribute_category FOREIGN KEY (category_id) REFERENCES attribute_category (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE attribute_option ADD CONSTRAINT FK_option_attribute FOREIGN KEY (attribute_id) REFERENCES cv_attribute (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE candidate_attribute_value ADD CONSTRAINT FK_value_user FOREIGN KEY (user_id) REFERENCES app_user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE candidate_attribute_value ADD CONSTRAINT FK_value_attribute FOREIGN KEY (attribute_id) REFERENCES cv_attribute (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE candidate_attribute_value ADD CONSTRAINT FK_value_option FOREIGN KEY (option_id) REFERENCES attribute_option (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE project ADD CONSTRAINT FK_project_owner FOREIGN KEY (owner_id) REFERENCES app_user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE project_tag ADD CONSTRAINT FK_project_tag_project FOREIGN KEY (project_id) REFERENCES project (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE project_tag ADD CONSTRAINT FK_project_tag_tag FOREIGN KEY (tag_id) REFERENCES tag (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE position_tag ADD CONSTRAINT FK_position_tag_position FOREIGN KEY (position_id) REFERENCES job_position (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE position_tag ADD CONSTRAINT FK_position_tag_tag FOREIGN KEY (tag_id) REFERENCES tag (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE position_attribute ADD CONSTRAINT FK_position_attribute_position FOREIGN KEY (position_id) REFERENCES job_position (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE position_attribute ADD CONSTRAINT FK_position_attribute_attribute FOREIGN KEY (attribute_id) REFERENCES cv_attribute (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE position_access_rule ADD CONSTRAINT FK_rule_position FOREIGN KEY (position_id) REFERENCES job_position (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE position_access_rule ADD CONSTRAINT FK_rule_attribute FOREIGN KEY (attribute_id) REFERENCES cv_attribute (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE cv ADD CONSTRAINT FK_cv_owner FOREIGN KEY (owner_id) REFERENCES app_user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE cv ADD CONSTRAINT FK_cv_position FOREIGN KEY (position_id) REFERENCES job_position (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE cv_like ADD CONSTRAINT FK_like_cv FOREIGN KEY (cv_id) REFERENCES cv (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE cv_like ADD CONSTRAINT FK_like_recruiter FOREIGN KEY (recruiter_id) REFERENCES app_user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE discussion_post ADD CONSTRAINT FK_post_position FOREIGN KEY (position_id) REFERENCES job_position (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE discussion_post ADD CONSTRAINT FK_post_author FOREIGN KEY (author_id) REFERENCES app_user (id) ON DELETE CASCADE');
        $this->addSql("INSERT INTO attribute_category (code) VALUES ('personal_information'), ('certification'), ('domain_knowledge'), ('soft_skills'), ('education'), ('languages'), ('other')");
        $this->addSql("INSERT INTO cv_attribute (category_id, name, description, type, builtin, code, created_at, updated_at, version) SELECT id, 'First Name', 'Given name', 'string', 1, 'first_name', NOW(), NOW(), 1 FROM attribute_category WHERE code = 'personal_information'");
        $this->addSql("INSERT INTO cv_attribute (category_id, name, description, type, builtin, code, created_at, updated_at, version) SELECT id, 'Last Name', 'Family name', 'string', 1, 'last_name', NOW(), NOW(), 1 FROM attribute_category WHERE code = 'personal_information'");
        $this->addSql("INSERT INTO cv_attribute (category_id, name, description, type, builtin, code, created_at, updated_at, version) SELECT id, 'Location', 'City or region', 'string', 1, 'location', NOW(), NOW(), 1 FROM attribute_category WHERE code = 'personal_information'");
        $this->addSql("INSERT INTO cv_attribute (category_id, name, description, type, builtin, code, created_at, updated_at, version) SELECT id, 'Personal Photo', 'Portrait', 'image', 1, 'personal_photo', NOW(), NOW(), 1 FROM attribute_category WHERE code = 'personal_information'");
        $this->addSql("INSERT INTO app_user (email, password, roles, blocked, locale, theme, created_at, updated_at, version) VALUES ('admin@example.com', '\$2y\$12\$HTs7UvowqZc.kQiBZKbKLuuyUakSfu51N58l8wk8tI8R50xCL3l5e', '[\"ROLE_ADMIN\",\"ROLE_RECRUITER\",\"ROLE_CANDIDATE\"]', 0, 'en', 'light', NOW(), NOW(), 1)");
        $this->addSql("INSERT INTO candidate_attribute_value (user_id, attribute_id, version, updated_at) SELECT u.id, a.id, 1, NOW() FROM app_user u JOIN cv_attribute a ON a.builtin = 1 WHERE u.email = 'admin@example.com'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE discussion_post DROP FOREIGN KEY FK_post_position');
        $this->addSql('ALTER TABLE discussion_post DROP FOREIGN KEY FK_post_author');
        $this->addSql('ALTER TABLE cv_like DROP FOREIGN KEY FK_like_cv');
        $this->addSql('ALTER TABLE cv_like DROP FOREIGN KEY FK_like_recruiter');
        $this->addSql('ALTER TABLE cv DROP FOREIGN KEY FK_cv_owner');
        $this->addSql('ALTER TABLE cv DROP FOREIGN KEY FK_cv_position');
        $this->addSql('ALTER TABLE position_access_rule DROP FOREIGN KEY FK_rule_position');
        $this->addSql('ALTER TABLE position_access_rule DROP FOREIGN KEY FK_rule_attribute');
        $this->addSql('ALTER TABLE position_attribute DROP FOREIGN KEY FK_position_attribute_position');
        $this->addSql('ALTER TABLE position_attribute DROP FOREIGN KEY FK_position_attribute_attribute');
        $this->addSql('ALTER TABLE position_tag DROP FOREIGN KEY FK_position_tag_position');
        $this->addSql('ALTER TABLE position_tag DROP FOREIGN KEY FK_position_tag_tag');
        $this->addSql('ALTER TABLE project_tag DROP FOREIGN KEY FK_project_tag_project');
        $this->addSql('ALTER TABLE project_tag DROP FOREIGN KEY FK_project_tag_tag');
        $this->addSql('ALTER TABLE project DROP FOREIGN KEY FK_project_owner');
        $this->addSql('ALTER TABLE candidate_attribute_value DROP FOREIGN KEY FK_value_user');
        $this->addSql('ALTER TABLE candidate_attribute_value DROP FOREIGN KEY FK_value_attribute');
        $this->addSql('ALTER TABLE candidate_attribute_value DROP FOREIGN KEY FK_value_option');
        $this->addSql('ALTER TABLE attribute_option DROP FOREIGN KEY FK_option_attribute');
        $this->addSql('ALTER TABLE cv_attribute DROP FOREIGN KEY FK_attribute_category');
        $this->addSql('ALTER TABLE social_account DROP FOREIGN KEY FK_social_user');
        $this->addSql('DROP TABLE discussion_post');
        $this->addSql('DROP TABLE cv_like');
        $this->addSql('DROP TABLE cv');
        $this->addSql('DROP TABLE position_access_rule');
        $this->addSql('DROP TABLE position_attribute');
        $this->addSql('DROP TABLE position_tag');
        $this->addSql('DROP TABLE job_position');
        $this->addSql('DROP TABLE project_tag');
        $this->addSql('DROP TABLE project');
        $this->addSql('DROP TABLE tag');
        $this->addSql('DROP TABLE candidate_attribute_value');
        $this->addSql('DROP TABLE attribute_option');
        $this->addSql('DROP TABLE cv_attribute');
        $this->addSql('DROP TABLE attribute_category');
        $this->addSql('DROP TABLE social_account');
        $this->addSql('DROP TABLE app_user');
    }
}
