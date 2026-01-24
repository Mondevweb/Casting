<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\DurationServiceTypeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DurationServiceTypeRepository::class)]
#[ApiResource]
class DurationServiceType extends AbstractServiceType
{
    // =========================================================================
    // RÈGLES DE STOCKAGE (Source 35)
    // =========================================================================

    #[ORM\Column]
    private ?int $libraryQuota = null;

    #[ORM\Column]
    private ?int $maxWeightMb = null;

    // =========================================================================
    // RÈGLES DE COMMANDE (Source 36)
    // =========================================================================

    #[ORM\Column]
    private ?int $orderMinFiles = 1; // Min de fichiers à envoyer

    #[ORM\Column]
    private ?int $orderMaxFiles = 1; // Max de fichiers à envoyer

    // =========================================================================
    // STRUCTURE DE PRIX (Source 37)
    // =========================================================================

    #[ORM\Column]
    private ?int $baseDurationMin = 2; // Le forfait inclut X minutes

    // =========================================================================
    // GETTERS & SETTERS
    // =========================================================================

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