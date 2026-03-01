<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\MediaFormatRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: MediaFormatRepository::class)]
#[ApiResource(
    operations: [
        new Get(),
        new GetCollection()
    ],
    normalizationContext: ['groups' => ['media_format:read']]
)]
class MediaFormat
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['media_format:read', 'service:read', 'professional:read', 'order:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['media_format:read', 'service:read', 'professional:read', 'order:read'])]
    private ?string $name = null; // Ex: "Images", "Vidéos MP4", "Documents PDF"

    #[ORM\Column(length: 255)]
    #[Groups(['media_format:read', 'service:read', 'professional:read', 'order:read'])]
    private ?string $slug = null; // Ex: "image", "video", "pdf"

    #[ORM\Column(length: 255)]
    #[Groups(['media_format:read', 'service:read', 'professional:read', 'order:read'])]
    private ?string $mimeTypeMask = null; // Ex: "image/*", "video/mp4,video/quicktime", "application/pdf"

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function getMimeTypeMask(): ?string
    {
        return $this->mimeTypeMask;
    }

    public function setMimeTypeMask(string $mimeTypeMask): static
    {
        $this->mimeTypeMask = $mimeTypeMask;

        return $this;
    }

    public function __toString(): string
    {
        return $this->name ?? 'Nouveau Format';
    }
}
