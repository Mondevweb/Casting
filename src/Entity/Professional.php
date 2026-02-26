<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use App\Repository\ProfessionalRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\JobTitle;
use App\Entity\Specialty;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Entity\ProService;
use App\State\ProfessionalProcessor;
use App\Enum\ProfessionalStatus;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Doctrine\Orm\Filter\RangeFilter;
use ApiPlatform\Doctrine\Orm\Filter\DateFilter;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: ProfessionalRepository::class)]
#[ApiResource(
    processor: ProfessionalProcessor::class,
    normalizationContext: ['groups' => ['professional:read']], // Pour la lecture (GET)
    denormalizationContext: ['groups' => ['professional:write']], // Pour l'écriture (POST/PATCH)
    paginationEnabled: false, // On charge tout pour filtrer en front (volumétrie < 200)
)]
#[ApiFilter(SearchFilter::class, properties: [
    'departmentCode' => 'exact',
    'jobTitle.name' => 'partial',
    'city' => 'partial',
    'zipCode' => 'exact',
    'specialties.name' => 'partial',
    'specialties.id' => 'exact',
    'proServices.serviceType.name' => 'partial'
])]
#[ApiFilter(BooleanFilter::class, properties: ['isExpressEnabled', 'isStripeVerified'])]
#[ApiFilter(RangeFilter::class, properties: ['standardDelayDays', 'proServices.basePrice'])]
#[ApiFilter(DateFilter::class, properties: ['unavailableUntil'])]
class Professional
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['professional:read'])]
    private ?int $id = null;

    // =========================================================================
    // RELATIONS TECHNIQUES
    // =========================================================================

    #[ORM\OneToOne(targetEntity: User::class, inversedBy: 'professional', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['professional:read'])] // En lecture, on pourra voir les infos du User lié
    private ?User $user = null;

    // =========================================================================
    // 1. IDENTITÉ PUBLIQUE (Source 3.3)
    // =========================================================================

    #[ORM\Column(length: 255)]
    #[Groups(['professional:read', 'professional:write'])]
    private ?string $firstName = null;

    #[ORM\Column(length: 255)]
    #[Groups(['professional:read', 'professional:write'])]
    private ?string $lastName = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['professional:read', 'professional:write'])]
    private ?string $avatarPath = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['professional:read', 'professional:write'])]
    private ?string $biography = null;

    #[ORM\ManyToOne(targetEntity: JobTitle::class, inversedBy: 'professionals')]
    #[ORM\JoinColumn(nullable: false)] // Un pro DOIT avoir un métier
    #[Groups(['professional:read', 'professional:write'])]
    private ?JobTitle $jobTitle = null;

    /**
     * @var Collection<int, Specialty>
     */
    #[ORM\ManyToMany(targetEntity: Specialty::class)]
    #[Groups(['professional:read', 'professional:write'])]
    private Collection $specialties;

    // =========================================================================
    // 2. LOCALISATION (Source 3.3 - Localisation)
    // =========================================================================

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['professional:read', 'professional:write'])]
    private ?string $address = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['professional:read', 'professional:write'])]
    private ?string $city = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Groups(['professional:read', 'professional:write'])]
    private ?string $zipCode = null;

    #[ORM\Column(length: 5, nullable: true)]
    #[Groups(['professional:read', 'professional:write'])]
    private ?string $departmentCode = null;

    // =========================================================================
    // 3. IDENTITÉ LÉGALE B2B (Source 3.3 - Identité Légale)
    // =========================================================================

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['professional:read', 'professional:write'])]
    private ?string $companyName = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(['professional:read', 'professional:write'])]
    private ?string $siretNumber = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['professional:read', 'professional:write'])]
    private ?string $billingAddress = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['professional:read', 'professional:write'])]
    private ?string $stripeAccountId = null;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['professional:read', 'professional:write'])]
    private ?bool $isStripeVerified = false;

    // =========================================================================
    // 4. PARAMÈTRES MAGASIN (Source 3.3 - Paramètres "Magasin")
    // =========================================================================

    #[ORM\Column]
    #[Groups(['professional:read', 'professional:write'])]
    private ?int $standardDelayDays = 7;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['professional:read', 'professional:write'])]
    private ?bool $isExpressEnabled = false;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    #[Groups(['professional:read', 'professional:write'])]
    private ?float $expressPremiumPercent = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['professional:read', 'professional:write'])]
    private ?int $maxActiveOrders = null;

    #[ORM\Column(enumType: ProfessionalStatus::class)]
    #[Groups(['professional:read', 'professional:write'])]
    private ProfessionalStatus $status = ProfessionalStatus::PENDING; // <--- Par défaut : EN ATTENTE

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['professional:read', 'professional:write'])]
    private ?\DateTimeInterface $unavailableUntil = null;

    #[ORM\OneToMany(mappedBy: 'professional', targetEntity: ProService::class, cascade: ['persist', 'remove'])]
    #[Groups(['professional:read', 'professional:write'])]
    private Collection $proServices;

    // --- CHAMPS VIRTUELS POUR L'INSCRIPTION (Non stockés en base) ---
    
    #[ApiProperty(readable: false, writable: true)]
    #[Groups(['professional:write'])]
    private ?string $email = null;

    #[ApiProperty(readable: false, writable: true)]
    #[Groups(['professional:write'])]
    private ?string $plainPassword = null;

    public function __construct()
    {
        $this->specialties = new ArrayCollection();
        $this->proServices = new ArrayCollection();
    }

    // =========================================================================
    // GETTERS & SETTERS
    // =========================================================================

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;
        return $this;
    }

    public function getAvatarPath(): ?string
    {
        return $this->avatarPath;
    }

    public function setAvatarPath(?string $avatarPath): static
    {
        $this->avatarPath = $avatarPath;
        return $this;
    }

    public function getBiography(): ?string
    {
        return $this->biography;
    }

    public function setBiography(?string $biography): static
    {
        $this->biography = $biography;
        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): static
    {
        $this->city = $city;
        return $this;
    }

    public function getZipCode(): ?string
    {
        return $this->zipCode;
    }

    public function setZipCode(?string $zipCode): static
    {
        $this->zipCode = $zipCode;
        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): static
    {
        $this->address = $address;
        return $this;
    }

    public function getDepartmentCode(): ?string
    {
        return $this->departmentCode;
    }

    public function setDepartmentCode(?string $departmentCode): static
    {
        $this->departmentCode = $departmentCode;
        return $this;
    }

    public function getCompanyName(): ?string
    {
        return $this->companyName;
    }

    public function setCompanyName(?string $companyName): static
    {
        $this->companyName = $companyName;
        return $this;
    }

    public function getSiretNumber(): ?string
    {
        return $this->siretNumber;
    }

    public function setSiretNumber(?string $siretNumber): static
    {
        $this->siretNumber = $siretNumber;
        return $this;
    }

    public function getBillingAddress(): ?string
    {
        return $this->billingAddress;
    }

    public function setBillingAddress(?string $billingAddress): static
    {
        $this->billingAddress = $billingAddress;
        return $this;
    }

    public function getStripeAccountId(): ?string
    {
        return $this->stripeAccountId;
    }

    public function setStripeAccountId(?string $stripeAccountId): static
    {
        $this->stripeAccountId = $stripeAccountId;
        return $this;
    }

    public function isStripeVerified(): ?bool
    {
        return $this->isStripeVerified;
    }

    public function setIsStripeVerified(bool $isStripeVerified): static
    {
        $this->isStripeVerified = $isStripeVerified;
        return $this;
    }

    public function getStandardDelayDays(): ?int
    {
        return $this->standardDelayDays;
    }

    public function setStandardDelayDays(int $standardDelayDays): static
    {
        $this->standardDelayDays = $standardDelayDays;
        return $this;
    }

    public function isExpressEnabled(): ?bool
    {
        return $this->isExpressEnabled;
    }

    public function setIsExpressEnabled(bool $isExpressEnabled): static
    {
        $this->isExpressEnabled = $isExpressEnabled;
        return $this;
    }

    public function getExpressPremiumPercent(): ?float
    {
        return $this->expressPremiumPercent;
    }

    public function setExpressPremiumPercent(?float $expressPremiumPercent): static
    {
        $this->expressPremiumPercent = $expressPremiumPercent;
        return $this;
    }

    public function getMaxActiveOrders(): ?int
    {
        return $this->maxActiveOrders;
    }

    public function setMaxActiveOrders(?int $maxActiveOrders): static
    {
        $this->maxActiveOrders = $maxActiveOrders;
        return $this;
    }

    public function getStatus(): ?ProfessionalStatus
    {
        return $this->status;
    }

    public function setStatus(ProfessionalStatus $status): static
    {       
        $this->status = $status;

        return $this;
    }

    public function getUnavailableUntil(): ?\DateTimeInterface
    {
        return $this->unavailableUntil;
    }

    public function setUnavailableUntil(?\DateTimeInterface $unavailableUntil): static
    {
        $this->unavailableUntil = $unavailableUntil;
        return $this;
    }

    public function getJobTitle(): ?JobTitle
    {
        return $this->jobTitle;
    }

    public function setJobTitle(?JobTitle $jobTitle): static
    {
        $this->jobTitle = $jobTitle;
        return $this;
    }

    /**
     * @return Collection<int, Specialty>
     */
    public function getSpecialties(): Collection
    {
        return $this->specialties;
    }

    public function addSpecialty(Specialty $specialty): static
    {
        if (!$this->specialties->contains($specialty)) {
            $this->specialties->add($specialty);
        }
        return $this;
    }

    public function removeSpecialty(Specialty $specialty): static
    {
        $this->specialties->removeElement($specialty);
        return $this;
    }

    /**
     * @return Collection<int, ProService>
     */
    public function getProServices(): Collection
    {
        return $this->proServices;
    }

    public function addProService(ProService $proService): static
    {
        if (!$this->proServices->contains($proService)) {
            $this->proServices->add($proService);
            $proService->setProfessional($this);
        }

        return $this;
    }

    public function removeProService(ProService $proService): static
    {
        if ($this->proServices->removeElement($proService)) {
            // set the owning side to null (unless already changed)
            if ($proService->getProfessional() === $this) {
                $proService->setProfessional(null);
            }
        }

        return $this;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setPlainPassword(string $plainPassword): static
    {
        $this->plainPassword = $plainPassword;
        return $this;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }
}