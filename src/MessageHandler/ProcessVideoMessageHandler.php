<?php

namespace App\MessageHandler;

use App\Message\ProcessVideoMessage;
use App\Repository\MediaObjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use FFMpeg\FFMpeg;
use FFMpeg\Format\Video\X264;
use FFMpeg\Coordinate\TimeCode;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use App\Enum\TranscodingStatus;

#[AsMessageHandler]
final class ProcessVideoMessageHandler
{
    public function __construct(
        private EntityManagerInterface $em,
        private MediaObjectRepository $mediaObjectRepository,
        private ParameterBagInterface $params,
    ) {
    }

    public function __invoke(ProcessVideoMessage $message): void
    {
        $mediaObject = $this->mediaObjectRepository->find($message->getMediaObjectId());

        if (!$mediaObject) {
            return;
        }

        // Marque le début du traitement
        $mediaObject->setTranscodingStatus(TranscodingStatus::PROCESSING);
        $this->em->flush();

        $ds = DIRECTORY_SEPARATOR;
        $uploadDir = $this->params->get('kernel.project_dir') . $ds . 'public' . $ds . 'uploads' . $ds . 'media' . $ds;
        $originalFilePath = $uploadDir . $mediaObject->getFilePath();

        if (!file_exists($originalFilePath)) {
            $mediaObject->setTranscodingStatus(TranscodingStatus::FAILED);
            $this->em->flush();
            return;
        }

        try {
            // Instanciation de FFmpeg (pointant vers les exécutables Laragon)
            $ffmpeg = FFMpeg::create([
                'ffmpeg.binaries'  => 'C:/laragon/bin/ffmpeg/ffmpeg.exe',
                'ffprobe.binaries' => 'C:/laragon/bin/ffmpeg/ffprobe.exe',
                'timeout'          => 3600, // 1 heure max
                'ffmpeg.threads'   => 4,   // Optimisation CPU
            ]);

            $video = $ffmpeg->open($originalFilePath);

            $fileInfo = pathinfo($mediaObject->getFilePath());
            $baseName = $fileInfo['filename'];
            
            // 1. GÉNÉRATION DE LA MINIATURE (à 1 seconde)
            $thumbnailFileName = $baseName . '_thumb.jpg';
            $thumbnailPath = $uploadDir . $thumbnailFileName;
            $frame = $video->frame(TimeCode::fromSeconds(1));
            $frame->save($thumbnailPath);
            $mediaObject->setThumbnailPath($thumbnailFileName);

            // 2. DÉTECTION DU CODEC D'ORIGINE
            $ffprobe = $ffmpeg->getFFProbe();
            $streams = $ffprobe->streams($originalFilePath)->videos()->first();
            $codec = $streams->get('codec_name');

            // 3. CONVERSION WEB (Sauf si c'est déjà du H.264)
            if ($codec === 'h264') {
                // Pas besoin de transcodage de flux vidéo. Le WebFilePath est le fichier original
                $mediaObject->setWebFilePath($mediaObject->getFilePath());
            } else {
                // Il faut transcoder (HEVC, AVI...)
                $webFileName = $baseName . '_web.mp4';
                $webFilePath = $uploadDir . $webFileName;
                
                $format = new X264('aac', 'libx264');
                $format->setAudioCodec("aac");     
                $format->setVideoCodec("libx264");
                
                $video->save($format, $webFilePath);
                $mediaObject->setWebFilePath($webFileName);
            }
            
            // Enregistrement final du succès
            $mediaObject->setTranscodingStatus(TranscodingStatus::COMPLETED);
            
        } catch (\Exception $e) {
            $mediaObject->setTranscodingStatus(TranscodingStatus::FAILED);
            file_put_contents($this->params->get('kernel.project_dir') . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'log' . DIRECTORY_SEPARATOR . 'ffmpeg_error.log', "[" . date('Y-m-d H:i:s') . "] FFMPEG ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
        }

        $this->em->flush();
    }
}
