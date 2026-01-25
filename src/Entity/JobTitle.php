<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Delete;
use App\Repository\JobTitleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use \Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: JobTitleRepository::class)]
#[ApiResource(
    operations: [
        new Get(),            // Public : Voir un métier
        new GetCollection(),  // Public : Liste pour le menu déroulant
        new Post(security: "is_granted('ROLE_ADMIN')"),   // Admin : Créer
        new Patch(security: "is_granted('ROLE_ADMIN')"),  // Admin : Modifier
        new Delete(security: "is_granted('ROLE_ADMIN')")  // Admin : Supprimer
    ],
    normalizationContext: ['groups' => ['jobtitle:read']],
    denormalizationContext: ['groups' => ['jobtitle:write']]
)]
#[UniqueEntity(fields: ['name'], message: 'Ce métier existe déjà dans la base de données.')]
class JobTitle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['jobtitle:read', 'professional:read'])] // Visible dans la liste des métiers ET quand on lit un pro
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: false, unique: true)]
    #[Groups(['jobtitle:read', 'jobtitle:write', 'professional:read'])]
    #[Assert\NotBlank(message: "Le nom du métier ne peut pas être vide.")]
    private ?string $name = null; // Ex: "Directeur de Casting", "Agent"

    // Relation inverse : Un JobTitle a plusieurs Professionals
    #[ORM\OneToMany(mappedBy: 'jobTitle', targetEntity: Professional::class)]
    private Collection $professionals;

    public function __construct()
    {
        $this->professionals = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return Collection<int, Professional>
     */
    public function getProfessionals(): Collection
    {
        return $this->professionals;
    }

    public function addProfessional(Professional $professional): static
    {
        if (!$this->professionals->contains($professional)) {
            $this->professionals->add($professional);
            $professional->setJobTitle($this);
        }

        return $this;
    }

    public function removeProfessional(Professional $professional): static
    {
        if ($this->professionals->removeElement($professional)) {
            // set the owning side to null (unless already changed)
            if ($professional->getJobTitle() === $this) {
                $professional->setJobTitle(null);
            }
        }

        return $this;
    }
}