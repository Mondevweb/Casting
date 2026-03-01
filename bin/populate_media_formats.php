<?php

require __DIR__.'/../vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;
(new Dotenv())->bootEnv(dirname(__DIR__).'/.env');

$kernel = new App\Kernel('dev', true);
$kernel->boot();

$em = $kernel->getContainer()->get('doctrine')->getManager();

use App\Entity\MediaFormat;
use App\Entity\AbstractServiceType;

$formatsData = [
    ['name' => 'Images (JPG, PNG...)', 'slug' => 'image', 'mask' => 'image/*'],
    ['name' => 'Vidéos (MP4, MOV)', 'slug' => 'video', 'mask' => 'video/mp4,video/quicktime'],
    ['name' => 'Documents (PDF)', 'slug' => 'pdf', 'mask' => 'application/pdf'],
    ['name' => 'Audio (MP3, WAV)', 'slug' => 'audio', 'mask' => 'audio/*']
];

$formats = [];
foreach ($formatsData as $data) {
    $existing = $em->getRepository(MediaFormat::class)->findOneBy(['slug' => $data['slug']]);
    if (!$existing) {
        $f = new MediaFormat();
        $f->setName($data['name']);
        $f->setSlug($data['slug']);
        $f->setMimeTypeMask($data['mask']);
        $em->persist($f);
        $formats[$data['slug']] = $f;
    } else {
        $formats[$data['slug']] = $existing;
    }
}

$em->flush();

// Link to existing ServiceTypes roughly based on their name for retro-compatibility
$serviceTypes = $em->getRepository(AbstractServiceType::class)->findAll();
foreach ($serviceTypes as $serviceType) {
    if ($serviceType->getAllowedMediaFormats()->isEmpty()) {
        $name = strtolower($serviceType->getName());
        $slug = strtolower($serviceType->getSlug());

        if (str_contains($name, 'photo') || str_contains($slug, 'photo') || str_contains($name, 'book') || str_contains($slug, 'book')) {
            $serviceType->addAllowedMediaFormat($formats['image']);
        }
        if (str_contains($name, 'vidéo') || str_contains($slug, 'video') || str_contains($name, 'démo') || str_contains($slug, 'demo') || str_contains($name, 'self-tape')) {
            $serviceType->addAllowedMediaFormat($formats['video']);
            $serviceType->addAllowedMediaFormat($formats['pdf']); // Pour les scénarios parfois jointes
        }
        if (str_contains($name, 'voix') || str_contains($slug, 'voix') || str_contains($name, 'audio')) {
            $serviceType->addAllowedMediaFormat($formats['audio']);
            $serviceType->addAllowedMediaFormat($formats['video']);
        }
        if (str_contains($name, 'cv') || str_contains($slug, 'cv') || str_contains($name, 'lettre') || str_contains($name, 'scénario')) {
            $serviceType->addAllowedMediaFormat($formats['pdf']);
        }
    }
}

$em->flush();
echo "Base de données peuplée avec succès !\n";

