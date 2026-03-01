<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Enum\OrderStatus;
use App\Repository\OrderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\OrderLine;
use App\Entity\OrderConclusion;
use App\Entity\Dispute;
use App\State\OrderProcessor;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: '`order`')] // "order" est un mot réservé SQL, il faut l'échapper
#[ApiResource(
    processor: OrderProcessor::class,
    denormalizationContext: ['groups' => ['order:write']],
    normalizationContext: ['groups' => ['order:read'], 'enable_max_depth' => true],
    forceEager: false,
    operations: [
        new \ApiPlatform\Metadata\Get(
            security: "object.getCandidate().getUser() === user or object.getProfessional().getUser() === user or is_granted('ROLE_ADMIN')"
        ),
        new \ApiPlatform\Metadata\GetCollection(
            // La sécurité des colections se gère souvent via un Extension (QueryExtension) pour filtrer les résultats
            // Mais on peut déjà bloquer l'accès général si besoin. Ici on laisse ouvert mais on filtrera via une Extension plus tard si on veut être propre.
            // Pour l'instant, on va sécuriser via le state provider ou une extension.
            // Simplification : On interdit GetCollection public, on laisse que pour Admin ? 
            // Non, un user veut voir SES commandes.
            // On va utiliser 'security' post-denormalize ? Non, c'est du GET.
            // Le mieux est de créer une DoctrineExtension pour filtrer les requêtes automatiquement.
        ),
        new \ApiPlatform\Metadata\Post(
            security: "is_granted('ROLE_CANDIDATE')"
        ),
        new \ApiPlatform\Metadata\Patch(
             security: "object.getCandidate().getUser() === user or object.getProfessional().getUser() === user"
        ),
        new \ApiPlatform\Metadata\Post(
            uriTemplate: '/orders/{id}/pay',
            controller: \App\Controller\OrderPaymentController::class,
            denormalizationContext: ['groups' => []], // Pas de body attendu ou spécifique
            read: true,
            description: 'Initiate payment for an order',
            name: 'pay_order',
            security: "object.getCandidate().getUser() === user"
        )
    ]
)]
#[ApiFilter(SearchFilter::class, properties: ['status' => 'exact'])]
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['order:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    #[Groups(['order:read'])]
    private ?string $reference = null; // Un ID unique lisible (ex: ORD-2026-XYZ)

    // =========================================================================
    // RELATIONS ACTEURS (Source 85)
    // =========================================================================

    #[ORM\ManyToOne(cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false)]
    #[ApiProperty(readableLink: false, writableLink: false)]
    #[Groups(['order:read', 'order:write'])]
    private ?Candidate $candidate = null;

    #[ORM\ManyToOne(cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['order:read', 'order:write'])]
    private ?Professional $professional = null;

    // =========================================================================
    // RELATIONS LIGNES DE COMMANDE (Source 5.1 -> 5.2)
    // =========================================================================

    /**
     * @var Collection<int, OrderLine>
     */
    #[ORM\OneToMany(mappedBy: 'order', targetEntity: OrderLine::class, cascade: ['persist', 'remove'])]
    #[ApiProperty(writableLink: true)]
    #[Groups(['order:read', 'order:write'])]
    #[MaxDepth(1)]
    private Collection $orderLines;

    // =========================================================================
    // ÉTAT & TEMPS (Source 86, 87, 88)
    // =========================================================================

    #[ORM\Column(length: 50, enumType: OrderStatus::class)]
    #[Groups(['order:read', 'order:write'])]
    private ?OrderStatus $status = OrderStatus::CART;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['order:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Groups(['order:read'])]
    private ?\DateTimeImmutable $paidAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Groups(['order:read'])]
    private ?\DateTimeImmutable $deliveredAt = null;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['order:read', 'order:write'])]
    private ?bool $isExpress = false;

    // =========================================================================
    // FINANCES (Source 89 à 93)
    // =========================================================================

    #[ORM\Column(type: Types::INTEGER)]
    #[Groups(['order:read'])]
    private ?int $totalAmountTtc = null; // Payé par le candidat (en centimes)

    #[ORM\Column(type: Types::FLOAT)]
    private ?float $appliedVatPercent = null; // Taux de TVA figé au moment de l'achat

    #[ORM\Column(type: Types::INTEGER)]
    private ?int $commissionAmount = null; // Revenu plateforme (en centimes)

    #[ORM\Column(type: Types::INTEGER)]
    private ?int $proAmount = null; // Revenu net pro (en centimes)

    // =========================================================================
    // CONCLUSION GLOBALE
    // =========================================================================

    #[ORM\OneToOne(mappedBy: 'orderRef', targetEntity: OrderConclusion::class, cascade: ['persist', 'remove'])]
    private ?OrderConclusion $orderConclusion = null;

    // =========================================================================
    // HISTORIQUE DES LITIGES
    // =========================================================================

    /**
     * @var Collection<int, Dispute>
     */
    #[ORM\OneToMany(mappedBy: 'orderRef', targetEntity: Dispute::class, cascade: ['persist', 'remove'])]
    private Collection $disputes;

    // =========================================================================
    // CONSTRUCTEUR
    // =========================================================================

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->status = OrderStatus::CART;
        $this->orderLines = new ArrayCollection();
        $this->disputes = new ArrayCollection();
    }

    // =========================================================================
    // GETTERS & SETTERS
    // =========================================================================

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(string $reference): static
    {
        $this->reference = $reference;
        return $this;
    }

    public function getCandidate(): ?Candidate
    {
        return $this->candidate;
    }

    public function setCandidate(?Candidate $candidate): static
    {
        $this->candidate = $candidate;
        return $this;
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

    public function getStatus(): ?OrderStatus
    {
        return $this->status;
    }

    public function setStatus(OrderStatus $status): static
    {
        $this->status = $status;
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

    public function getPaidAt(): ?\DateTimeImmutable
    {
        return $this->paidAt;
    }

    public function setPaidAt(?\DateTimeImmutable $paidAt): static
    {
        $this->paidAt = $paidAt;
        return $this;
    }

    public function getDeliveredAt(): ?\DateTimeImmutable
    {
        return $this->deliveredAt;
    }

    public function setDeliveredAt(?\DateTimeImmutable $deliveredAt): static
    {
        $this->deliveredAt = $deliveredAt;
        return $this;
    }

    public function isExpress(): ?bool
    {
        return $this->isExpress;
    }

    public function setIsExpress(bool $isExpress): static
    {
        $this->isExpress = $isExpress;
        return $this;
    }

    public function getTotalAmountTtc(): ?int
    {
        return $this->totalAmountTtc;
    }

    public function setTotalAmountTtc(int $totalAmountTtc): static
    {
        $this->totalAmountTtc = $totalAmountTtc;
        return $this;
    }

    public function getAppliedVatPercent(): ?float
    {
        return $this->appliedVatPercent;
    }

    public function setAppliedVatPercent(float $appliedVatPercent): static
    {
        $this->appliedVatPercent = $appliedVatPercent;
        return $this;
    }

    public function getCommissionAmount(): ?int
    {
        return $this->commissionAmount;
    }

    public function setCommissionAmount(int $commissionAmount): static
    {
        $this->commissionAmount = $commissionAmount;
        return $this;
    }

    public function getProAmount(): ?int
    {
        return $this->proAmount;
    }

    public function setProAmount(int $proAmount): static
    {
        $this->proAmount = $proAmount;
        return $this;
    }

    /**
     * @return Collection<int, OrderLine>
     */
    public function getOrderLines(): Collection
    {
        return $this->orderLines;
    }

    public function addOrderLine(OrderLine $orderLine): static
    {
        if (!$this->orderLines->contains($orderLine)) {
            $this->orderLines->add($orderLine);
            $orderLine->setOrder($this);
        }
        return $this;
    }

    public function removeOrderLine(OrderLine $orderLine): static
    {
        if ($this->orderLines->removeElement($orderLine)) {
            // set the owning side to null (unless already changed)
            if ($orderLine->getOrder() === $this) {
                $orderLine->setOrder(null);
            }
        }
        return $this;
    }

    public function getOrderConclusion(): ?OrderConclusion
    {
        return $this->orderConclusion;
    }

    public function setOrderConclusion(OrderConclusion $orderConclusion): static
    {
        // set the owning side of the relation if necessary
        if ($orderConclusion->getOrderRef() !== $this) {
            $orderConclusion->setOrderRef($this);
        }

        $this->orderConclusion = $orderConclusion;

        return $this;
    }

    /**
     * @return Collection<int, Dispute>
     */
    public function getDisputes(): Collection
    {
        return $this->disputes;
    }

    public function addDispute(Dispute $dispute): static
    {
        if (!$this->disputes->contains($dispute)) {
            $this->disputes->add($dispute);
            $dispute->setOrderRef($this);
        }

        return $this;
    }

    public function removeDispute(Dispute $dispute): static
    {
        if ($this->disputes->removeElement($dispute)) {
            // set the owning side to null (unless already changed)
            if ($dispute->getOrderRef() === $this) {
                $dispute->setOrderRef(null);
            }
        }

        return $this;
    }
}