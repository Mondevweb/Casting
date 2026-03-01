<?php

namespace App\Controller;

use App\Entity\MediaObject;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Messenger\MessageBusInterface;
use App\Message\ProcessVideoMessage;

#[AsController]
class CreateMediaObjectAction extends AbstractController
{
    public function __invoke(Request $request, EntityManagerInterface $em, MessageBusInterface $bus): JsonResponse
    {
        $uploadedFiles = $request->files->get('file');
        
        if (!$uploadedFiles) {
            return new JsonResponse(['error' => '"file" est requis'], 400);
        }

        // Si ce n'est pas un tableau (upload simple), on le transforme en tableau pour traiter de la même manière
        if (!is_array($uploadedFiles)) {
            $uploadedFiles = [$uploadedFiles];
        }

        // Vérifier que le candidat est bien renseigné
        $user = $this->getUser();
        if (!$user instanceof User || !$user->getCandidate()) {
            return new JsonResponse(['error' => 'Profil Candidat requis pour l\'upload'], 403);
        }
        $candidate = $user->getCandidate();

        $responses = [];

        foreach ($uploadedFiles as $uploadedFile) {
            $mediaObject = new MediaObject();
            $mediaObject->setFile($uploadedFile);
            
            // Catégorie envoyée depuis PrimeVue
            if ($request->request->has('category')) {
                 $categoryEnum = \App\Enum\MediaCategory::tryFrom($request->request->get('category'));
                 if ($categoryEnum) {
                     $mediaObject->setCategory($categoryEnum);
                 } else {
                     $mediaObject->setCategory(\App\Enum\MediaCategory::PHOTO);
                 }
            } else {
                 $mediaObject->setCategory(\App\Enum\MediaCategory::PHOTO);
            }
            
            $mediaObject->setCandidate($candidate);
            
            // Détection du MimeType et appel asynchrone FFmpeg si Vidéo
            $isVideo = $uploadedFile->getMimeType() && str_starts_with($uploadedFile->getMimeType(), 'video/');
            if ($isVideo) {
                // On prévient l'UI que c'est en attente
                $mediaObject->setTranscodingStatus(\App\Enum\TranscodingStatus::PENDING);
            }

            // Persistance et écriture
            $em->persist($mediaObject);
            $em->flush(); // Flush ici pour obtenir l'ID (VichUploader agira)

            if ($isVideo) {
                $bus->dispatch(new ProcessVideoMessage($mediaObject->getId()));
            }

            $responses[] = [
                '@context' => '/api/contexts/MediaObject',
                '@id' => '/api/media_objects/' . $mediaObject->getId(),
                '@type' => 'MediaObject',
                'id' => $mediaObject->getId(),
                'filePath' => $mediaObject->getFilePath(),
                'originalName' => $mediaObject->getOriginalName(),
                'mimeType' => $mediaObject->getMimeType(),
                'transcodingStatus' => $mediaObject->getTranscodingStatus()->value,
                'webFilePath' => $mediaObject->getWebFilePath(),
                'thumbnailPath' => $mediaObject->getThumbnailPath()
            ];
        }

        // Si on a envoyé un tableau de fichiers, on renvoie le tableau complet.
        // Si on en a envoyé qu'un seul (format non-tableau d'origine), on renvoie l'objet simple pour ne pas casser d'autres clients éventuels.
        if (count($responses) === 1 && !is_array($request->files->get('file'))) {
            return new JsonResponse($responses[0], 201);
        }

        return new JsonResponse($responses, 201);
    }
}
