<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\DurationServiceTypeRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;

#[ORM\Entity(repositoryClass: DurationServiceTypeRepository::class)]
#[ApiResource(
    normalizationContext: ['groups' => ['service:read']], // On active le groupe
    operations: [
        new Get(),
        new GetCollection()
    ]
)]
class DurationServiceType extends AbstractServiceType
{
    // =========================================================================
    // RÈGLES DE STOCKAGE (Source 35)
    // =========================================================================

    #[ORM\Column]
    #[Groups(['service:read'])]
    private ?int $libraryQuota = 10;

    #[ORM\Column]
    #[Groups(['service:read'])]
    private ?int $maxWeightMb = 500;

    // =========================================================================
    // RÈGLES DE COMMANDE (Source 36)
    // =========================================================================

    #[ORM\Column]
    #[Groups(['service:read'])]
    private ?int $orderMinFiles = 1; // Min de fichiers à envoyer

    #[ORM\Column]
    #[Groups(['service:read'])]
    private ?int $orderMaxFiles = 1; // Max de fichiers à envoyer

    // =========================================================================
    // STRUCTURE DE PRIX (Source 37)
    // =========================================================================

    #[ORM\Column(type: 'integer')]
    #[Groups(['service:read', 'professional:read', 'order:read'])]
    private ?int $baseDurationMin = null; // Durée de base 

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Groups(['service:read', 'professional:read', 'order:read'])]
    private ?int $durationStep = null; // Pas d'incrément temps (ex: 60 min = 1h)

    // =========================================================================
    // GETTERS & SETTERS
    // =========================================================================

    public function getDurationStep(): ?int
    {
        return $this->durationStep;
    }

    public function setDurationStep(int $durationStep): static
    {
        $this->durationStep = $durationStep;
        return $this;
    }

    public function getLibraryQuota(): ?int
    {
        return $this->libraryQuota;
    }

    public function setLibraryQuota(int $libraryQuota): static
    {
        $this->libraryQuota = $libraryQuota;
        return $this;
    }

    public function getMaxWeightMb(): ?int
    {
        return $this->maxWeightMb;
    }

    public function setMaxWeightMb(int $maxWeightMb): static
    {
        $this->maxWeightMb = $maxWeightMb;
        return $this;
    }

    public function getOrderMinFiles(): ?int
    {
        return $this->orderMinFiles;
    }

    public function setOrderMinFiles(int $orderMinFiles): static
    {
        $this->orderMinFiles = $orderMinFiles;
        return $this;
    }

    public function getOrderMaxFiles(): ?int
    {
        return $this->orderMaxFiles;
    }

    public function setOrderMaxFiles(int $orderMaxFiles): static
    {
        $this->orderMaxFiles = $orderMaxFiles;
        return $this;
    }

    public function getBaseDurationMin(): ?int
    {
        return $this->baseDurationMin;
    }

    public function setBaseDurationMin(int $baseDurationMin): static
    {
        $this->baseDurationMin = $baseDurationMin;
        return $this;
    }
}