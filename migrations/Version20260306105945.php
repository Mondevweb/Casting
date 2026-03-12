<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260306105945 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE media_object ADD service_type_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE media_object ADD CONSTRAINT FK_14D43132AC8DE0F FOREIGN KEY (service_type_id) REFERENCES abstract_service_type (id)');
        $this->addSql('CREATE INDEX IDX_14D43132AC8DE0F ON media_object (service_type_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE media_object DROP FOREIGN KEY FK_14D43132AC8DE0F');
        $this->addSql('DROP INDEX IDX_14D43132AC8DE0F ON media_object');
        $this->addSql('ALTER TABLE media_object DROP service_type_id');
    }
}
