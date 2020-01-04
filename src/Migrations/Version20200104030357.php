<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200104030357 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql(
            <<<SQL
CREATE TABLE authentication_token (
    id INT AUTO_INCREMENT NOT NULL,
    service VARCHAR(255) NOT NULL,
    user_id INT NOT NULL,
    access_token VARCHAR(255) NOT NULL,
    expires_in INT NOT NULL,
    scope LONGTEXT NOT NULL,
    token_type VARCHAR(255) NOT NULL,
    refresh_token VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY(id),
    INDEX service (service),
    INDEX user_id (user_id)
    ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
SQL
        );
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE authentication_token');
    }
}
