<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260228111813 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE abstract_service_type_media_format (abstract_service_type_id INT NOT NULL, media_format_id INT NOT NULL, INDEX IDX_E5564298C006FC1A (abstract_service_type_id), INDEX IDX_E5564298F349458B (media_format_id), PRIMARY KEY(abstract_service_type_id, media_format_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE accepted_media_type (id INT AUTO_INCREMENT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE media_format (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, mime_type_mask VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE abstract_service_type_media_format ADD CONSTRAINT FK_E5564298C006FC1A FOREIGN KEY (abstract_service_type_id) REFERENCES abstract_service_type (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE abstract_service_type_media_format ADD CONSTRAINT FK_E5564298F349458B FOREIGN KEY (media_format_id) REFERENCES media_format (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE duration_service_type CHANGE duration_step duration_step INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE abstract_service_type_media_format DROP FOREIGN KEY FK_E5564298C006FC1A');
        $this->addSql('ALTER TABLE abstract_service_type_media_format DROP FOREIGN KEY FK_E5564298F349458B');
        $this->addSql('DROP TABLE abstract_service_type_media_format');
        $this->addSql('DROP TABLE accepted_media_type');
        $this->addSql('DROP TABLE media_format');
        $this->addSql('ALTER TABLE duration_service_type CHANGE duration_step duration_step INT NOT NULL');
    }
}
