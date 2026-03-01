<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\AbstractServiceTypeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: AbstractServiceTypeRepository::class)]
#[ORM\InheritanceType('JOINED')]
#[ORM\DiscriminatorColumn(name: 'discriminator', type: 'string')]
#[ORM\DiscriminatorMap([
    'unit' => UnitServiceType::class,
    'duration' => DurationServiceType::class
])]
#[ApiResource(
    normalizationContext: ['groups' => ['service:read']],
    operations: [
        new Get(),
        new GetCollection()
    ]
)]
abstract class AbstractServiceType
{
    public function __construct()
    {
        $this->allowedMediaFormats = new ArrayCollection();
    }
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['service:read', 'professional:read', 'order:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['service:read', 'professional:read', 'order:read'])]
    private ?string $name = null; // Ex: "Photos", "Bande Démo"

    #[ORM\Column(length: 255)]
    #[Groups(['service:read', 'professional:read', 'order:read'])]
    private ?string $slug = null; // Ex: "photos", "demo-reel"

    #[ORM\Column(type: 'boolean')]
    #[Groups(['service:read', 'professional:read', 'order:read'])]
    private ?bool $isActive = true;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['service:read', 'professional:read', 'order:read'])]
    private ?string $description = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['service:read', 'professional:read', 'order:read'])]
    private ?string $instructionsHelp = null; // Texte d'instruction ou consigne spécifique au format demandé, par la plateforme

    #[ORM\Column(type: 'boolean')]
    #[Groups(['service:read', 'professional:read', 'order:read'])]
    private ?bool $isExpressAllowed = false; // Peut-on demander du 48h ?

    #[ORM\ManyToMany(targetEntity: MediaFormat::class)]
    #[Groups(['service:read', 'professional:read', 'order:read'])]
    private Collection $allowedMediaFormats;

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

    public function isActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getInstructionsHelp(): ?string
    {
        return $this->instructionsHelp;
    }

    public function setInstructionsHelp(?string $instructionsHelp): static
    {
        $this->instructionsHelp = $instructionsHelp;
        return $this;
    }

    public function isExpressAllowed(): ?bool
    {
        return $this->isExpressAllowed;
    }

    public function setIsExpressAllowed(bool $isExpressAllowed): static
    {
        $this->isExpressAllowed = $isExpressAllowed;
        return $this;
    }

    #[Groups(['service:read', 'professional:read', 'order:read'])]
    public function getDiscriminator(): string
    {
        return $this instanceof DurationServiceType ? 'duration' : 'unit';
    }

    /**
     * @return Collection<int, MediaFormat>
     */
    public function getAllowedMediaFormats(): Collection
    {
        return $this->allowedMediaFormats;
    }

    public function addAllowedMediaFormat(MediaFormat $mediaFormat): static
    {
        if (!$this->allowedMediaFormats->contains($mediaFormat)) {
            $this->allowedMediaFormats->add($mediaFormat);
        }

        return $this;
    }

    public function removeAllowedMediaFormat(MediaFormat $mediaFormat): static
    {
        $this->allowedMediaFormats->removeElement($mediaFormat);

        return $this;
    }

    /**
     * Helper pour le frontend VueJS (Construit le mask Html à partir des relations M2M)
     */
    #[Groups(['service:read', 'professional:read', 'order:read'])]
    public function getHtmlAcceptMask(): string
    {
        if ($this->allowedMediaFormats->isEmpty()) {
            return "image/*,video/mp4,video/quicktime,video/x-msvideo,application/pdf,audio/*"; // Fallback permissif
        }

        $masks = [];
        foreach ($this->allowedMediaFormats as $format) {
            $mask = $format->getMimeTypeMask();
            if ($mask && !in_array($mask, $masks)) {
                $masks[] = $mask;
            }
        }

        return implode(',', $masks);
    }
}