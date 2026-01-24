<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Enum\InvoiceType;
use App\Repository\InvoiceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InvoiceRepository::class)]
#[ApiResource]
class Invoice
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // =========================================================================
    // RELATION (Source 108)
    // =========================================================================

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Order $order = null;

    // =========================================================================
    // DÉTAILS DOCUMENT (Source 109, 110, 111)
    // =========================================================================

    #[ORM\Column(length: 50, enumType: InvoiceType::class)]
    private ?InvoiceType $type = null;

    #[ORM\Column(length: 255)]
    private ?string $invoiceNumber = null; // Ex: INV-2026-0001

    #[ORM\Column(length: 255)]
    private ?string $filePath = null; // Chemin vers le PDF stocké (S3/Local)

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    // =========================================================================
    // CONSTRUCTEUR
    // =========================================================================

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

    public function getOrder(): ?Order
    {
        return $this->order;
    }

    public function setOrder(?Order $order): static
    {
        $this->order = $order;
        return $this;
    }

    public function getType(): ?InvoiceType
    {
        return $this->type;
    }

    public function setType(InvoiceType $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getInvoiceNumber(): ?string
    {
        return $this->invoiceNumber;
    }

    public function setInvoiceNumber(string $invoiceNumber): static
    {
        $this->invoiceNumber = $invoiceNumber;
        return $this;
    }

    public function getFilePath(): ?string
    {
        return $this->filePath;
    }

    public function setFilePath(string $filePath): static
    {
        $this->filePath = $filePath;
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
}