<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use App\Entity\Candidate;
use App\Entity\MediaObject;

use App\Enum\TranscodingStatus;
use App\Repository\AbstractServiceTypeRepository;
use Doctrine\ORM\EntityManagerInterface;

class BunnyStreamController extends AbstractController
{
    private $httpClient;
    private $apiKey;
    private $libraryId;

    public function __construct(HttpClientInterface $httpClient, string $bunnyStreamApiKey = '', string $bunnyStreamLibraryId = '')
    {
        $this->httpClient = $httpClient;
        $this->apiKey = $_ENV['BUNNY_STREAM_API_KEY'] ?? $bunnyStreamApiKey;
        $this->libraryId = $_ENV['BUNNY_STREAM_LIBRARY_ID'] ?? $bunnyStreamLibraryId;
    }

    #[Route('/api/bunny-stream/create-video', name: 'api_bunny_create_video', methods: ['POST'])]
    public function createVideo(Request $request, \Psr\Log\LoggerInterface $logger): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User || !$user->getCandidate()) {
            return new JsonResponse(['error' => 'Unauthorized. Must be a candidate.'], 401);
        }
        $candidate = $user->getCandidate();

        $data = json_decode($request->getContent(), true);
        $title = $data['title'] ?? 'Video Upload ' . date('Y-m-d H:i');

        try {
            $response = $this->httpClient->request('POST', "https://video.bunnycdn.com/library/{$this->libraryId}/videos", [
                'headers' => [
                    'AccessKey' => $this->apiKey,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
                'json' => [
                    'title' => $title
                ]
            ]);

            $statusCode = $response->getStatusCode();
            if ($statusCode !== 200 && $statusCode !== 201) {
                return new JsonResponse($response->toArray(false), $statusCode);
            }

            $bunnyVideoData = $response->toArray();
            $videoId = $bunnyVideoData['guid'];
            
            // Bunny.net Secure Direct Upload Signature Authentication
            // SHA256(LibraryId + ApiKey + ExpirationTime + VideoId)
            // L'expiration est fixée à +24 heures (86400 secondes)
            $expirationTime = time() + 86400;
            $signatureString = $this->libraryId . $this->apiKey . $expirationTime . $videoId;
            $signature = hash('sha256', $signatureString);

            // On renvoie les infos de signature au front
            return new JsonResponse([
                'videoId' => $videoId,
                'libraryId' => $this->libraryId,
                'authorizationSignature' => $signature,
                'authorizationExpire' => $expirationTime,
            ]);

        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    #[Route('/api/bunny-stream/finalize-video', name: 'api_bunny_finalize_video', methods: ['POST'])]
    public function finalizeVideo(Request $request, EntityManagerInterface $em, AbstractServiceTypeRepository $serviceTypeRepo): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User || !$user->getCandidate()) {
            return new JsonResponse(['error' => 'Unauthorized. Must be a candidate.'], 401);
        }
        $candidate = $user->getCandidate();

        $data = json_decode($request->getContent(), true);
        $videoId = $data['videoId'] ?? null;
        $serviceTypeId = $data['serviceTypeId'] ?? null;
        $originalName = $data['originalName'] ?? 'video_upload.mp4';

        if (!$videoId || !$serviceTypeId) {
            return new JsonResponse(['error' => 'Missing videoId or serviceTypeId'], 400);
        }

        $serviceType = $serviceTypeRepo->find($serviceTypeId);
        if (!$serviceType) {
            return new JsonResponse(['error' => 'ServiceType not found'], 404);
        }

        $media = new MediaObject();
        $media->setBunnyVideoId($videoId);
        $media->setCandidate($candidate);
        $media->setServiceType($serviceType);

        $media->setOriginalName($originalName);
        $media->setTranscodingStatus(TranscodingStatus::PROCESSING);

        $em->persist($media);
        $em->flush();
        return new JsonResponse([
            '@id' => '/api/media_objects/' . $media->getId(),
            'id' => $media->getId(),
            'bunnyVideoId' => $media->getBunnyVideoId(),
            'originalName' => $media->getOriginalName(),
            'transcodingStatus' => $media->getTranscodingStatus()?->value,
            'contentUrl' => $media->getContentUrl(),
            'msg' => 'MediaObject created successfully and pending Bunny Webhook'
        ], 201);
    }

    #[Route('/api/webhooks/bunny', name: 'api_bunny_webhook', methods: ['POST'])]
    public function handleWebhook(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);
        if (!$payload || !isset($payload['VideoId'])) {
            return new JsonResponse(['status' => 'ignored'], 200);
        }

        $videoId = $payload['VideoId'];
        $status = $payload['Status'] ?? -1;

        // Status 4 = Finished Encoding
        if ($status !== 4 && $status !== 5) {
            return new JsonResponse(['status' => 'not_finished_yet'], 200);
        }

        $mediaRepo = $em->getRepository(MediaObject::class);
        $media = $mediaRepo->findOneBy(['bunnyVideoId' => $videoId]);

        if (!$media) {
            return new JsonResponse(['status' => 'media_not_found'], 404);
        }

        if ($status === 4) {
            $media->setTranscodingStatus(TranscodingStatus::COMPLETED);
        } elseif ($status === 5) {
            $media->setTranscodingStatus(TranscodingStatus::ERROR);
        }

        $em->flush();

        return new JsonResponse(['status' => 'success'], 200);
    }

    #[Route('/api/bunny-stream/check-video/{bunnyVideoId}', name: 'api_bunny_check_video', methods: ['GET'])]
    public function checkVideoStatus(string $bunnyVideoId, EntityManagerInterface $em): JsonResponse
    {
        // Endpoint très utile pour l'environnement local (où les webhooks ne fonctionnent pas)
        // ou comme fallback.
        
        $mediaRepo = $em->getRepository(MediaObject::class);
        $media = $mediaRepo->findOneBy(['bunnyVideoId' => $bunnyVideoId]);

        if (!$media) {
            return new JsonResponse(['error' => 'Media not found'], 404);
        }

        try {
            $response = $this->httpClient->request('GET', "https://video.bunnycdn.com/library/{$this->libraryId}/videos/{$bunnyVideoId}", [
                'headers' => [
                    'AccessKey' => $this->apiKey,
                    'Accept' => 'application/json',
                ]
            ]);

            if ($response->getStatusCode() === 200) {
                $data = $response->toArray();
                $status = $data['status'] ?? -1;

                // 4 = Finished
                if ($status === 4) {
                    $media->setTranscodingStatus(TranscodingStatus::COMPLETED);
                    $em->flush();
                } elseif ($status === 5) {
                    $media->setTranscodingStatus(TranscodingStatus::ERROR);
                    $em->flush();
                }

                return new JsonResponse([
                    'status' => $status,
                    'transcodingStatus' => $media->getTranscodingStatus()->value
                ]);
            }
            
            return new JsonResponse(['error' => 'Failed to fetch from Bunny'], $response->getStatusCode());

        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }
}
