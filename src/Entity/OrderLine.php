<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\OrderLineRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Analysis;
use Symfony\Component\Serializer\Attribute\Groups;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Delete;

#[ORM\Entity(repositoryClass: OrderLineRepository::class)]
#[ApiResource(
    normalizationContext: ['groups' => ['order:read']], 
    denormalizationContext: ['groups' => ['order:write']],
    
    operations: [
        // On autorise à voir une ligne seule (par son ID)
        new Get(),
        // On autorise la création (souvent utile si le front ajoute item par item)
        new Post(),
        // On autorise la modification (changer la quantité, changer les instructions)
        new Patch(),
        // On autorise la suppression (retirer du panier)
        new Delete(),
        // ⚠️ PAS DE GetCollection() ! On ne veut pas lister toutes les lignes du monde.
    ]
)]
class OrderLine
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // =========================================================================
    // RELATIONS PARENTES (Source 94, 95)
    // =========================================================================

    #[ORM\ManyToOne(inversedBy: 'orderLines')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Order $order = null; // Attention : J'ai nommé la propriété "order" (sans 'Ref')

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['order:read', 'order:write'])]
    private ?AbstractServiceType $serviceType = null; // Le type de service (Photo, Vidéo...)

    #[ORM\ManyToOne]
    private ?ProService $service = null; // Le service pro lié (pour le prix)

    // =========================================================================
    // CONTENU À ANALYSER (Source 101)
    // =========================================================================

    /**
     * @var Collection<int, MediaObject>
     */
    #[ORM\ManyToMany(targetEntity: MediaObject::class)]
    #[Groups(['order:read', 'order:write'])]
    private Collection $mediaObjects;

    // =========================================================================
    // SNAPSHOT PRIX (Immuable) (Source 96, 97)
    // =========================================================================
    // On copie le prix ici au moment de l'achat. Si le Pro change ses tarifs
    // demain, cette commande ne doit pas bouger.

    #[ORM\Column(type: Types::INTEGER)]
    #[Groups(['order:read'])]
    private ?int $unitPriceFrozen = null; // Prix unitaire figé

    #[ORM\Column(type: Types::INTEGER)]
    #[Groups(['order:read'])]
    private ?int $basePriceFrozen = null; // Prix forfait figé

    // =========================================================================
    // DÉTAILS FACTURATION (Source 98, 99, 100)
    // =========================================================================

    #[ORM\Column]
    #[Groups(['order:read', 'order:write'])]
    private ?int $quantityBilled = null; // Nb photos ou Minutes facturées

    #[ORM\Column(type: Types::INTEGER)]
    #[Groups(['order:read'])]
    private ?int $lineTotalAmount = null; // Total de la ligne

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['order:read', 'order:write'])]
    private ?string $instructions = null; // Le message du candidat pour cette ligne

    // =========================================================================
    // ANALYSE
    // =========================================================================
    #[ORM\OneToOne(mappedBy: 'orderLine', targetEntity: Analysis::class, cascade: ['persist', 'remove'])]
    private ?Analysis $analysis = null;

    // =========================================================================
    // CONSTRUCTEUR
    // =========================================================================

    public function __construct()
    {
        $this->mediaObjects = new ArrayCollection();
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

    public function getServiceType(): ?AbstractServiceType
    {
        return $this->serviceType;
    }

    public function setServiceType(?AbstractServiceType $serviceType): static
    {
        $this->serviceType = $serviceType;
        return $this;
    }

    public function getService(): ?ProService
    {
        return $this->service;
    }

    public function setService(?ProService $service): static
    {
        $this->service = $service;
        return $this;
    }

    /**
     * @return Collection<int, MediaObject>
     */
    public function getMediaObjects(): Collection
    {
        return $this->mediaObjects;
    }

    public function addMediaObject(MediaObject $mediaObject): static
    {
        if (!$this->mediaObjects->contains($mediaObject)) {
            $this->mediaObjects->add($mediaObject);
        }
        return $this;
    }

    public function removeMediaObject(MediaObject $mediaObject): static
    {
        $this->mediaObjects->removeElement($mediaObject);
        return $this;
    }

    public function getUnitPriceFrozen(): ?int
    {
        return $this->unitPriceFrozen;
    }

    public function setUnitPriceFrozen(int $unitPriceFrozen): static
    {
        $this->unitPriceFrozen = $unitPriceFrozen;
        return $this;
    }

    public function getBasePriceFrozen(): ?int
    {
        return $this->basePriceFrozen;
    }

    public function setBasePriceFrozen(int $basePriceFrozen): static
    {
        $this->basePriceFrozen = $basePriceFrozen;
        return $this;
    }

    public function getQuantityBilled(): ?int
    {
        return $this->quantityBilled;
    }

    public function setQuantityBilled(int $quantityBilled): static
    {
        $this->quantityBilled = $quantityBilled;
        return $this;
    }

    public function getLineTotalAmount(): ?int
    {
        return $this->lineTotalAmount;
    }

    public function setLineTotalAmount(int $lineTotalAmount): static
    {
        $this->lineTotalAmount = $lineTotalAmount;
        return $this;
    }

    public function getInstructions(): ?string
    {
        return $this->instructions;
    }

    public function setInstructions(?string $instructions): static
    {
        $this->instructions = $instructions;
        return $this;
    }

    public function getAnalysis(): ?Analysis
    {
        return $this->analysis;
    }

    public function setAnalysis(Analysis $analysis): static
    {
        // set the owning side of the relation if necessary
        if ($analysis->getOrderLine() !== $this) {
            $analysis->setOrderLine($this);
        }

        $this->analysis = $analysis;

        return $this;
    }
}