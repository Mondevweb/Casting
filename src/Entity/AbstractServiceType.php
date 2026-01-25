<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\AbstractServiceTypeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

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
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['service:read', 'professional:read'])]
    private ?string $name = null; // Ex: "Photos", "Bande Démo"

    #[ORM\Column(length: 255)]
    #[Groups(['service:read', 'professional:read'])]
    private ?string $slug = null; // Ex: "photos", "demo-reel"

    #[ORM\Column(type: 'boolean')]
    #[Groups(['service:read', 'professional:read'])]
    private ?bool $isActive = true;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['service:read', 'professional:read'])]
    private ?string $description = null;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['service:read', 'professional:read'])]
    private ?bool $isExpressAllowed = false; // Peut-on demander du 48h ?

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

    public function isExpressAllowed(): ?bool
    {
        return $this->isExpressAllowed;
    }

    public function setIsExpressAllowed(bool $isExpressAllowed): static
    {
        $this->isExpressAllowed = $isExpressAllowed;
        return $this;
    }
}