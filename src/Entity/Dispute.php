<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Enum\DisputeReason;
use App\Enum\DisputeStatus;
use App\Repository\DisputeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: DisputeRepository::class)]
#[ApiResource]
class Dispute
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // =========================================================================
    // RELATIONS (Source 130)
    // =========================================================================

    #[ORM\ManyToOne(inversedBy: 'disputes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Order $orderRef = null;

    // =========================================================================
    // LA PLAINTE DU CANDIDAT (Source 131, 132)
    // =========================================================================

    #[ORM\Column(length: 50, enumType: DisputeReason::class)]
    private ?DisputeReason $reason = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\Length(min: 200)] // Règle source 923 : min 200 caractères
    private ?string $message = null;

    // =========================================================================
    // LA DÉCISION ADMIN (Source 133, 134)
    // =========================================================================

    #[ORM\Column(length: 50, enumType: DisputeStatus::class)]
    private ?DisputeStatus $status = DisputeStatus::OPEN;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $adminComment = null; // Justification du refus ou consignes

    // =========================================================================
    // HORODATAGE (Source 135, 136)
    // =========================================================================

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $resolvedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->status = DisputeStatus::OPEN;
    }

    // =========================================================================
    // GETTERS & SETTERS
    // =========================================================================

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrderRef(): ?Order
    {
        return $this->orderRef;
    }

    public function setOrderRef(?Order $orderRef): static
    {
        $this->orderRef = $orderRef;
        return $this;
    }

    public function getReason(): ?DisputeReason
    {
        return $this->reason;
    }

    public function setReason(DisputeReason $reason): static
    {
        $this->reason = $reason;
        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): static
    {
        $this->message = $message;
        return $this;
    }

    public function getStatus(): ?DisputeStatus
    {
        return $this->status;
    }

    public function setStatus(DisputeStatus $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getAdminComment(): ?string
    {
        return $this->adminComment;
    }

    public function setAdminComment(?string $adminComment): static
    {
        $this->adminComment = $adminComment;
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

    public function getResolvedAt(): ?\DateTimeImmutable
    {
        return $this->resolvedAt;
    }

    public function setResolvedAt(?\DateTimeImmutable $resolvedAt): static
    {
        $this->resolvedAt = $resolvedAt;
        return $this;
    }
}