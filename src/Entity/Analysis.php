<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Enum\AnalysisInterest;
use App\Repository\AnalysisRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AnalysisRepository::class)]
#[ApiResource]
class Analysis
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // =========================================================================
    // RELATIONS (Source 115)
    // =========================================================================

    #[ORM\OneToOne(inversedBy: 'analysis', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?OrderLine $orderLine = null;

    // =========================================================================
    // CONTENU DE L'ANALYSE (Source 116, 117, 118, 119)
    // =========================================================================

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank]
    private ?string $content = null; // Le feedback textuel détaillé

    #[ORM\Column(type: Types::SMALLINT)]
    #[Assert\Range(min: 1, max: 5)]
    private ?int $rating = null; // Note de 1 à 5 (Recommandation finale)

    /**
     * Stocke l'ordre de préférence des médias sous forme de tableau d'IDs.
     * Ex: [42, 12, 55] (L'image 42 est la mieux classée)
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private array $rankingData = [];

    #[ORM\Column(length: 50, enumType: AnalysisInterest::class)]
    private ?AnalysisInterest $meetInterest = null; // "Ce matériel vous donne-t-il envie de le rencontrer ?"

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

    public function getOrderLine(): ?OrderLine
    {
        return $this->orderLine;
    }

    public function setOrderLine(OrderLine $orderLine): static
    {
        $this->orderLine = $orderLine;
        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;
        return $this;
    }

    public function getRating(): ?int
    {
        return $this->rating;
    }

    public function setRating(int $rating): static
    {
        $this->rating = $rating;
        return $this;
    }

    public function getRankingData(): array
    {
        return $this->rankingData;
    }

    public function setRankingData(?array $rankingData): static
    {
        $this->rankingData = $rankingData ?? [];
        return $this;
    }

    public function getMeetInterest(): ?AnalysisInterest
    {
        return $this->meetInterest;
    }

    public function setMeetInterest(AnalysisInterest $meetInterest): static
    {
        $this->meetInterest = $meetInterest;
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