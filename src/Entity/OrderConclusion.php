<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Enum\AnalysisInterest;
use App\Repository\OrderConclusionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: OrderConclusionRepository::class)]
#[ApiResource]
class OrderConclusion
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // =========================================================================
    // RELATIONS (Source 123)
    // =========================================================================

    #[ORM\OneToOne(inversedBy: 'orderConclusion', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?Order $orderRef = null; // Attention au nommage: $orderRef pour éviter conflit mot clé SQL

    // =========================================================================
    // CONTENU DE LA SYNTHÈSE (Source 124, 125, 126, 127)
    // =========================================================================

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank]
    private ?string $globalReview = null; // Synthèse générale

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $strengths = null; // Points forts

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $improvements = null; // Axes d'amélioration

    #[ORM\Column(length: 50, enumType: AnalysisInterest::class)]
    private ?AnalysisInterest $finalMeetInterest = null; // "Auriez-vous envie de rencontrer ce comédien ?"

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

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

    public function getOrderRef(): ?Order
    {
        return $this->orderRef;
    }

    public function setOrderRef(Order $orderRef): static
    {
        $this->orderRef = $orderRef;
        return $this;
    }

    public function getGlobalReview(): ?string
    {
        return $this->globalReview;
    }

    public function setGlobalReview(string $globalReview): static
    {
        $this->globalReview = $globalReview;
        return $this;
    }

    public function getStrengths(): ?string
    {
        return $this->strengths;
    }

    public function setStrengths(?string $strengths): static
    {
        $this->strengths = $strengths;
        return $this;
    }

    public function getImprovements(): ?string
    {
        return $this->improvements;
    }

    public function setImprovements(?string $improvements): static
    {
        $this->improvements = $improvements;
        return $this;
    }

    public function getFinalMeetInterest(): ?AnalysisInterest
    {
        return $this->finalMeetInterest;
    }

    public function setFinalMeetInterest(AnalysisInterest $finalMeetInterest): static
    {
        $this->finalMeetInterest = $finalMeetInterest;
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