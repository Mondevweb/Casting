<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\UnitServiceTypeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UnitServiceTypeRepository::class)]
#[ApiResource]
class UnitServiceType extends AbstractServiceType
{
    // =========================================================================
    // RÈGLES DE STOCKAGE (Source 30)
    // =========================================================================

    #[ORM\Column]
    private ?int $libraryQuota = null; // Nb max de fichiers stockables

    #[ORM\Column]
    private ?int $maxWeightMb = null; // Poids max par fichier (MB)

    // =========================================================================
    // RÈGLES DE COMMANDE (Source 31)
    // =========================================================================

    #[ORM\Column]
    private ?int $orderMinQty = 1;

    #[ORM\Column]
    private ?int $orderMaxQty = 10;

    // =========================================================================
    // STRUCTURE DE PRIX (Source 32)
    // =========================================================================

    #[ORM\Column]
    private ?int $baseQuantity = 1; // Le forfait de base inclut X photos

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

    public function getOrderMinQty(): ?int
    {
        return $this->orderMinQty;
    }

    public function setOrderMinQty(int $orderMinQty): static
    {
        $this->orderMinQty = $orderMinQty;
        return $this;
    }

    public function getOrderMaxQty(): ?int
    {
        return $this->orderMaxQty;
    }

    public function setOrderMaxQty(int $orderMaxQty): static
    {
        $this->orderMaxQty = $orderMaxQty;
        return $this;
    }

    public function getBaseQuantity(): ?int
    {
        return $this->baseQuantity;
    }

    public function setBaseQuantity(int $baseQuantity): static
    {
        $this->baseQuantity = $baseQuantity;
        return $this;
    }
}