<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Delete;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Annotation\Groups;
use Doctrine\ORM\Mapping as ORM;
use App\Enum\MediaCategory;
use App\Enum\TranscodingStatus;
use Doctrine\DBAL\Types\Types;
use Symfony\Component\Validator\Constraints as Assert;
use Gedmo\Mapping\Annotation as Gedmo;
use App\Repository\MediaObjectRepository;

#[ORM\Entity(repositoryClass: MediaObjectRepository::class)]
#[ApiResource(
    types: ['https://schema.org/MediaObject'],
    operations: [
        new Get(),
        new GetCollection(),
        new Post(
            controller: \App\Controller\CreateMediaObjectAction::class, 
            deserialize: false, 
            validate: false,
            inputFormats: ['multipart' => ['multipart/form-data']]
        ),
        new Delete()
    ],
    normalizationContext: ['groups' => ['media_object:read']]
)]
#[Vich\Uploadable]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt', timeAware: false)]
class MediaObject
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['media_object:read', 'order:read', 'candidate:read'])]
    private ?int $id = null;

    // =========================================================================
    // RELATIONS (Source 76: owner_id)
    // =========================================================================

    #[ORM\ManyToOne(inversedBy: 'mediaObjects')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Candidate $candidate = null;

    // =========================================================================
    // FICHIER & MÉTDADONNÉES (Source 77, 78)
    // =========================================================================

    #[Vich\UploadableField(mapping: 'media_object', fileNameProperty: 'filePath', size: 'size', mimeType: 'mimeType', originalName: 'originalName')]
    private ?File $file = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['media_object:read', 'candidate:read', 'order:read'])]
    private ?string $contentUrl = null; // Calculated field if needed, or use filePath with prefix

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['media_object:read', 'order:read'])]
    private ?string $filePath = null; // Le chemin S3 ou local (ex: /uploads/cv_jean.pdf)

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['media_object:read', 'order:read'])]
    private ?string $originalName = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(['media_object:read', 'order:read'])]
    private ?string $mimeType = null; // ex: application/pdf, image/jpeg

    #[ORM\Column(nullable: true)]
    private ?int $size = null; // En octets

    // =========================================================================
    // CATÉGORISATION (Source 79, 80)
    // =========================================================================

    #[ORM\Column(length: 255, enumType: MediaCategory::class)]
    #[Assert\NotNull]
    private ?MediaCategory $category = null;

    #[ORM\Column(nullable: true)]
    private ?int $duration = null; // En secondes (uniquement pour vidéos)

    // =========================================================================
    // TRANSCODAGE (FFmpeg)
    // =========================================================================

    #[ORM\Column(length: 255, enumType: TranscodingStatus::class)]
    #[Groups(['media_object:read', 'order:read', 'candidate:read'])]
    private ?TranscodingStatus $transcodingStatus = TranscodingStatus::NOT_APPLICABLE;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['media_object:read', 'order:read', 'candidate:read'])]
    private ?string $webFilePath = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['media_object:read', 'order:read', 'candidate:read'])]
    private ?string $thumbnailPath = null;

    // =========================================================================
    // TRAÇABILITÉ (Source 81)
    // =========================================================================

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    // =========================================================================
    // SOFT DELETE (Suppression Logique)
    // =========================================================================

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $deletedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    // =========================================================================
    // GETTERS & SETTERS
    // =========================================================================

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCandidate(): ?Candidate
    {
        return $this->candidate;
    }

    public function setCandidate(?Candidate $candidate): static
    {
        $this->candidate = $candidate;
        return $this;
    }

    public function getFilePath(): ?string
    {
        return $this->filePath;
    }

    public function setFilePath(?string $filePath): static
    {
        $this->filePath = $filePath;
        return $this;
    }

    public function getOriginalName(): ?string
    {
        return $this->originalName;
    }

    public function setOriginalName(?string $originalName): static
    {
        $this->originalName = $originalName;
        return $this;
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function setMimeType(?string $mimeType): static
    {
        $this->mimeType = $mimeType;
        return $this;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function setSize(?int $size): static
    {
        $this->size = $size;
        return $this;
    }

    public function getCategory(): ?MediaCategory
    {
        return $this->category;
    }

    public function setCategory(MediaCategory $category): static
    {
        $this->category = $category;
        return $this;
    }

    public function getDuration(): ?int
    {
        return isset($this->duration) ? $this->duration : null;
    }

    public function setDuration(?int $duration): static
    {
        $this->duration = $duration;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getDeletedAt(): ?\DateTimeInterface
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?\DateTimeInterface $deletedAt): static
    {
        $this->deletedAt = $deletedAt;
        return $this;
    }

    public function getTranscodingStatus(): ?TranscodingStatus
    {
        return $this->transcodingStatus;
    }

    public function setTranscodingStatus(TranscodingStatus $transcodingStatus): static
    {
        $this->transcodingStatus = $transcodingStatus;
        return $this;
    }

    public function getWebFilePath(): ?string
    {
        return $this->webFilePath;
    }

    public function setWebFilePath(?string $webFilePath): static
    {
        $this->webFilePath = $webFilePath;
        return $this;
    }

    public function getThumbnailPath(): ?string
    {
        return $this->thumbnailPath;
    }

    public function setThumbnailPath(?string $thumbnailPath): static
    {
        $this->thumbnailPath = $thumbnailPath;
        return $this;
    }
    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    public function setFile(?File $file = null): void
    {
        $this->file = $file;

        if (null !== $file) {
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getFile(): ?File
    {
        return $this->file;
    }
}