<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260305130417 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE media_object ADD content_url VARCHAR(255) DEFAULT NULL, ADD updated_at DATETIME DEFAULT NULL, CHANGE file_path file_path VARCHAR(255) DEFAULT NULL, CHANGE original_name original_name VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE order_line ADD service_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE order_line ADD CONSTRAINT FK_9CE58EE1ED5CA9E6 FOREIGN KEY (service_id) REFERENCES pro_service (id)');
        $this->addSql('CREATE INDEX IDX_9CE58EE1ED5CA9E6 ON order_line (service_id)');
        $this->addSql('ALTER TABLE unit_service_type ADD unit_name VARCHAR(50) NOT NULL');

        $this->addSql("INSERT INTO media_format (id, name, slug, mime_type_mask) VALUES 
            (1, 'Images', 'image', 'image/*'),
            (2, 'Vidéos', 'video', 'video/mp4,video/quicktime,video/x-msvideo'),
            (3, 'Documents PDF', 'pdf', 'application/pdf'),
            (4, 'Audio', 'audio', 'audio/*')
        ");

        $this->addSql("INSERT INTO abstract_service_type (id, discriminator, name, slug, is_active, description, instructions_help, is_express_allowed) VALUES
            (1, 'unit', 'Analyse de Photos', 'analyse-de-photos', 1, 'Un retour professionnel et détaillé sur vos headshots ou photos de book. Idéal pour sélectionner les clichés les plus percutants pour l\'industrie.', 'Merci d\'uploader vos meilleures propositions photographiques. Précisez si vous ciblez la publicité, la mode ou le cinéma.', 0),
            (2, 'duration', 'Analyse de Bande Démo', 'analyse-de-bande-demo', 1, 'Critique constructive de votre bande démo. Analyse du rythme, du choix des scènes et de l\'impact global pour maximiser vos chances de décrocher des auditions.', 'Uploadez votre bande démo actuelle (fichier vidéo compressé de préférence). Précisez dans le champ de texte la typologie de rôles vers laquelle vous souhaitez évoluer.', 0),
            (3, 'duration', 'Analyse de Vidéo de Présentation', 'analyse-de-video-presentation', 1, 'Évaluation de votre vidéo de présentation (selftape d\'introduction). Conseils sur l\'attitude, la lumière, le cadre et le son pour garantir une première impression mémorable.', 'Uploadez une courte vidéo (1 minute max) où vous vous présentez face caméra de manière naturelle.', 0),
            (4, 'unit', 'Analyse de CV', 'analyse-de-cv', 1, 'Relecture complète et optimisation de votre Curriculum Vitae artistique. Mise en valeur stratégique de vos expériences et de votre formation.', 'Votre CV doit impérativement être envoyé au format PDF pour éviter tout problème de mise en page. Précisez votre taille, âge apparent et lien vers vos réseaux si pertinents.', 0),
            (5, 'duration', 'Composition de Bande Démo', 'composition-de-bande-demo', 1, 'Montage sur mesure de votre bande démo à partir de vos rushs bruts ou extraits de films. Création d\'une narration visuelle dynamique mettant en valeur votre spectre de jeu.', 'Déposez ici les différentes vidéos contenant vos apparitions. Attention, vous devez OBLIGATOIREMENT fournir un document PDF listant, pour chaque vidéo envoyée, le TimeCode précis de votre apparition (ex: Fichier 2, de 01:14 à 01:45).', 0)
        ");

        $this->addSql("INSERT INTO abstract_service_type_media_format (abstract_service_type_id, media_format_id) VALUES 
            (1, 1),
            (2, 2),
            (3, 2),
            (4, 3),
            (5, 2)
        ");

        $this->addSql("INSERT INTO unit_service_type (id, unit_name, base_quantity, order_min_qty, order_max_qty, library_quota, max_weight_mb) VALUES
            (1, 'photo', 1, 1, 10, 50, 50),
            (4, 'CV', 1, 1, 1, 10, 50)
        ");

        $this->addSql("INSERT INTO duration_service_type (id, base_duration_min, duration_step, order_min_files, order_max_files, library_quota, max_weight_mb) VALUES
            (2, 15, 15, 1, 1, 10, 500),
            (3, 5, 5, 1, 1, 10, 500),
            (5, 3, 1, 1, 10, 20, 500)
        ");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE media_object DROP content_url, DROP updated_at, CHANGE file_path file_path VARCHAR(255) NOT NULL, CHANGE original_name original_name VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE order_line DROP FOREIGN KEY FK_9CE58EE1ED5CA9E6');
        $this->addSql('DROP INDEX IDX_9CE58EE1ED5CA9E6 ON order_line');
        $this->addSql('ALTER TABLE order_line DROP service_id');
        $this->addSql('ALTER TABLE unit_service_type DROP unit_name');

        $this->addSql("DELETE FROM abstract_service_type_media_format WHERE abstract_service_type_id IN (1, 2, 3, 4, 5)");
        $this->addSql("DELETE FROM unit_service_type WHERE id IN (1, 4)");
        $this->addSql("DELETE FROM duration_service_type WHERE id IN (2, 3, 5)");
        $this->addSql("DELETE FROM abstract_service_type WHERE id IN (1, 2, 3, 4, 5)");
        $this->addSql("DELETE FROM media_format WHERE id IN (1, 2, 3, 4)");
    }
}
