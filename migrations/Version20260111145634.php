<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260111145634 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE abstract_service_type (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, is_active TINYINT(1) NOT NULL, description LONGTEXT DEFAULT NULL, is_express_allowed TINYINT(1) NOT NULL, discriminator VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE analysis (id INT AUTO_INCREMENT NOT NULL, order_line_id INT NOT NULL, content LONGTEXT NOT NULL, rating SMALLINT NOT NULL, ranking_data JSON DEFAULT NULL, meet_interest VARCHAR(50) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_33C730BB01DC09 (order_line_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE dispute (id INT AUTO_INCREMENT NOT NULL, order_ref_id INT NOT NULL, reason VARCHAR(50) NOT NULL, message LONGTEXT NOT NULL, status VARCHAR(50) NOT NULL, admin_comment LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', resolved_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_3C925007E238517C (order_ref_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE duration_service_type (id INT NOT NULL, library_quota INT NOT NULL, max_weight_mb INT NOT NULL, order_min_files INT NOT NULL, order_max_files INT NOT NULL, base_duration_min INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE invoice (id INT AUTO_INCREMENT NOT NULL, order_id INT NOT NULL, type VARCHAR(50) NOT NULL, invoice_number VARCHAR(255) NOT NULL, file_path VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_906517448D9F6D38 (order_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE job_title (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE media_object (id INT AUTO_INCREMENT NOT NULL, candidate_id INT NOT NULL, file_path VARCHAR(255) NOT NULL, original_name VARCHAR(255) NOT NULL, mime_type VARCHAR(50) DEFAULT NULL, size INT DEFAULT NULL, category VARCHAR(255) NOT NULL, duration INT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', deleted_at DATETIME DEFAULT NULL, INDEX IDX_14D4313291BD8781 (candidate_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE `order` (id INT AUTO_INCREMENT NOT NULL, candidate_id INT NOT NULL, professional_id INT NOT NULL, reference VARCHAR(255) NOT NULL, status VARCHAR(50) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', paid_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', is_express TINYINT(1) NOT NULL, total_amount_ttc INT NOT NULL, applied_vat_percent DOUBLE PRECISION NOT NULL, commission_amount INT NOT NULL, pro_amount INT NOT NULL, UNIQUE INDEX UNIQ_F5299398AEA34913 (reference), INDEX IDX_F529939891BD8781 (candidate_id), INDEX IDX_F5299398DB77003 (professional_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE order_conclusion (id INT AUTO_INCREMENT NOT NULL, order_ref_id INT NOT NULL, global_review LONGTEXT NOT NULL, strengths LONGTEXT DEFAULT NULL, improvements LONGTEXT DEFAULT NULL, final_meet_interest VARCHAR(50) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_AE82E4A0E238517C (order_ref_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE order_line (id INT AUTO_INCREMENT NOT NULL, order_id INT NOT NULL, service_type_id INT NOT NULL, unit_price_frozen INT NOT NULL, base_price_frozen INT NOT NULL, quantity_billed INT NOT NULL, line_total_amount INT NOT NULL, instructions LONGTEXT DEFAULT NULL, INDEX IDX_9CE58EE18D9F6D38 (order_id), INDEX IDX_9CE58EE1AC8DE0F (service_type_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE order_line_media_object (order_line_id INT NOT NULL, media_object_id INT NOT NULL, INDEX IDX_3983AE2BBB01DC09 (order_line_id), INDEX IDX_3983AE2B64DE5A5 (media_object_id), PRIMARY KEY(order_line_id, media_object_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE pro_service (id INT AUTO_INCREMENT NOT NULL, professional_id INT NOT NULL, service_type_id INT NOT NULL, is_active TINYINT(1) NOT NULL, base_price INT NOT NULL, supplement_price INT DEFAULT NULL, INDEX IDX_14345C09DB77003 (professional_id), INDEX IDX_14345C09AC8DE0F (service_type_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE professional_specialty (professional_id INT NOT NULL, specialty_id INT NOT NULL, INDEX IDX_5525EF07DB77003 (professional_id), INDEX IDX_5525EF079A353316 (specialty_id), PRIMARY KEY(professional_id, specialty_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE specialty (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE unit_service_type (id INT NOT NULL, library_quota INT NOT NULL, max_weight_mb INT NOT NULL, order_min_qty INT NOT NULL, order_max_qty INT NOT NULL, base_quantity INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE analysis ADD CONSTRAINT FK_33C730BB01DC09 FOREIGN KEY (order_line_id) REFERENCES order_line (id)');
        $this->addSql('ALTER TABLE dispute ADD CONSTRAINT FK_3C925007E238517C FOREIGN KEY (order_ref_id) REFERENCES `order` (id)');
        $this->addSql('ALTER TABLE duration_service_type ADD CONSTRAINT FK_65BB0A94BF396750 FOREIGN KEY (id) REFERENCES abstract_service_type (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE invoice ADD CONSTRAINT FK_906517448D9F6D38 FOREIGN KEY (order_id) REFERENCES `order` (id)');
        $this->addSql('ALTER TABLE media_object ADD CONSTRAINT FK_14D4313291BD8781 FOREIGN KEY (candidate_id) REFERENCES candidate (id)');
        $this->addSql('ALTER TABLE `order` ADD CONSTRAINT FK_F529939891BD8781 FOREIGN KEY (candidate_id) REFERENCES candidate (id)');
        $this->addSql('ALTER TABLE `order` ADD CONSTRAINT FK_F5299398DB77003 FOREIGN KEY (professional_id) REFERENCES professional (id)');
        $this->addSql('ALTER TABLE order_conclusion ADD CONSTRAINT FK_AE82E4A0E238517C FOREIGN KEY (order_ref_id) REFERENCES `order` (id)');
        $this->addSql('ALTER TABLE order_line ADD CONSTRAINT FK_9CE58EE18D9F6D38 FOREIGN KEY (order_id) REFERENCES `order` (id)');
        $this->addSql('ALTER TABLE order_line ADD CONSTRAINT FK_9CE58EE1AC8DE0F FOREIGN KEY (service_type_id) REFERENCES abstract_service_type (id)');
        $this->addSql('ALTER TABLE order_line_media_object ADD CONSTRAINT FK_3983AE2BBB01DC09 FOREIGN KEY (order_line_id) REFERENCES order_line (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE order_line_media_object ADD CONSTRAINT FK_3983AE2B64DE5A5 FOREIGN KEY (media_object_id) REFERENCES media_object (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE pro_service ADD CONSTRAINT FK_14345C09DB77003 FOREIGN KEY (professional_id) REFERENCES professional (id)');
        $this->addSql('ALTER TABLE pro_service ADD CONSTRAINT FK_14345C09AC8DE0F FOREIGN KEY (service_type_id) REFERENCES abstract_service_type (id)');
        $this->addSql('ALTER TABLE professional_specialty ADD CONSTRAINT FK_5525EF07DB77003 FOREIGN KEY (professional_id) REFERENCES professional (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE professional_specialty ADD CONSTRAINT FK_5525EF079A353316 FOREIGN KEY (specialty_id) REFERENCES specialty (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE unit_service_type ADD CONSTRAINT FK_900B0152BF396750 FOREIGN KEY (id) REFERENCES abstract_service_type (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE professional ADD job_title_id INT NOT NULL');
        $this->addSql('ALTER TABLE professional ADD CONSTRAINT FK_B3B573AA6DD822C6 FOREIGN KEY (job_title_id) REFERENCES job_title (id)');
        $this->addSql('CREATE INDEX IDX_B3B573AA6DD822C6 ON professional (job_title_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE professional DROP FOREIGN KEY FK_B3B573AA6DD822C6');
        $this->addSql('ALTER TABLE analysis DROP FOREIGN KEY FK_33C730BB01DC09');
        $this->addSql('ALTER TABLE dispute DROP FOREIGN KEY FK_3C925007E238517C');
        $this->addSql('ALTER TABLE duration_service_type DROP FOREIGN KEY FK_65BB0A94BF396750');
        $this->addSql('ALTER TABLE invoice DROP FOREIGN KEY FK_906517448D9F6D38');
        $this->addSql('ALTER TABLE media_object DROP FOREIGN KEY FK_14D4313291BD8781');
        $this->addSql('ALTER TABLE `order` DROP FOREIGN KEY FK_F529939891BD8781');
        $this->addSql('ALTER TABLE `order` DROP FOREIGN KEY FK_F5299398DB77003');
        $this->addSql('ALTER TABLE order_conclusion DROP FOREIGN KEY FK_AE82E4A0E238517C');
        $this->addSql('ALTER TABLE order_line DROP FOREIGN KEY FK_9CE58EE18D9F6D38');
        $this->addSql('ALTER TABLE order_line DROP FOREIGN KEY FK_9CE58EE1AC8DE0F');
        $this->addSql('ALTER TABLE order_line_media_object DROP FOREIGN KEY FK_3983AE2BBB01DC09');
        $this->addSql('ALTER TABLE order_line_media_object DROP FOREIGN KEY FK_3983AE2B64DE5A5');
        $this->addSql('ALTER TABLE pro_service DROP FOREIGN KEY FK_14345C09DB77003');
        $this->addSql('ALTER TABLE pro_service DROP FOREIGN KEY FK_14345C09AC8DE0F');
        $this->addSql('ALTER TABLE professional_specialty DROP FOREIGN KEY FK_5525EF07DB77003');
        $this->addSql('ALTER TABLE professional_specialty DROP FOREIGN KEY FK_5525EF079A353316');
        $this->addSql('ALTER TABLE unit_service_type DROP FOREIGN KEY FK_900B0152BF396750');
        $this->addSql('DROP TABLE abstract_service_type');
        $this->addSql('DROP TABLE analysis');
        $this->addSql('DROP TABLE dispute');
        $this->addSql('DROP TABLE duration_service_type');
        $this->addSql('DROP TABLE invoice');
        $this->addSql('DROP TABLE job_title');
        $this->addSql('DROP TABLE media_object');
        $this->addSql('DROP TABLE `order`');
        $this->addSql('DROP TABLE order_conclusion');
        $this->addSql('DROP TABLE order_line');
        $this->addSql('DROP TABLE order_line_media_object');
        $this->addSql('DROP TABLE pro_service');
        $this->addSql('DROP TABLE professional_specialty');
        $this->addSql('DROP TABLE specialty');
        $this->addSql('DROP TABLE unit_service_type');
        $this->addSql('DROP INDEX IDX_B3B573AA6DD822C6 ON professional');
        $this->addSql('ALTER TABLE professional DROP job_title_id');
    }
}
