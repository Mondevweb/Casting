<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\ProServiceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: ProServiceRepository::class)]
#[ApiResource]
class ProService
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['professional:read'])]
    private ?int $id = null;

    // =========================================================================
    // RELATIONS (Liaison Pro <-> Catalogue) (Source 70)
    // =========================================================================

    #[ORM\ManyToOne(inversedBy: 'proServices')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Professional $professional = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['professional:read'])]
    private ?AbstractServiceType $serviceType = null;

    // =========================================================================
    // ÉTAT & TARIFICATION (Source 71, 72, 73)
    // =========================================================================

    #[ORM\Column(type: 'boolean')]
    #[Groups(['professional:read'])]
    private ?bool $isActive = false; // Le pro propose-t-il ce service actuellement ?

    #[ORM\Column(type: Types::INTEGER)]
    #[Groups(['professional:read'])]
    private ?int $basePrice = null; // Prix du forfait (en centimes ou euros selon votre choix, ici Int = centimes souvent)

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Groups(['professional:read'])]
    private ?int $supplementPrice = null; // Prix unitaire/minute sup

    // =========================================================================
    // GETTERS & SETTERS
    // =========================================================================

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProfessional(): ?Professional
    {
        return $this->professional;
    }

    public function setProfessional(?Professional $professional): static
    {
        $this->professional = $professional;
        return $this;
    }

    public function getServiceType(): ?AbstractServiceType
    {
        return $this->serviceType;
    }

    public function setServiceType(?AbstractServiceType $serviceType): static
    {
        $this->serviceType = $serviceType;
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

    public function getBasePrice(): ?int
    {
        return $this->basePrice;
    }

    public function setBasePrice(int $basePrice): static
    {
        $this->basePrice = $basePrice;
        return $this;
    }

    public function getSupplementPrice(): ?int
    {
        return $this->supplementPrice;
    }

    public function setSupplementPrice(?int $supplementPrice): static
    {
        $this->supplementPrice = $supplementPrice;
        return $this;
    }
}