<?php

namespace App\Entity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Security\Core\User\UserInterface; // Importe l'interface UserInterface
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;// Importe l'interface PasswordAuthenticatedUserInterface
use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserRepository::class)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    private ?string $password = null;

    #[ORM\Column]
    private array $roles = [];

    /**
     * @var Collection<int, Syllabus>
     */
    #[ORM\OneToMany(targetEntity: Syllabus::class, mappedBy: 'owner', orphanRemoval: true)]
    private Collection $syllabi;

    public function __construct()
    {
        $this->syllabi = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }
    
    // Implémentation de la méthode getUserIdentifier() requise par UserInterface
    public function getUserIdentifier(): string
    {
        return $this->email;  // Identifiant unique
    }

    /**
     * @return Collection<int, Syllabus>
     */
    public function getSyllabi(): Collection
    {
        return $this->syllabi;
    }

    public function addSyllabus(Syllabus $syllabus): static
    {
        if (!$this->syllabi->contains($syllabus)) {
            $this->syllabi->add($syllabus);
            $syllabus->setOwner($this);
        }

        return $this;
    }

    public function removeSyllabus(Syllabus $syllabus): static
    {
        if ($this->syllabi->removeElement($syllabus)) {
            // set the owning side to null (unless already changed)
            if ($syllabus->getOwner() === $this) {
                $syllabus->setOwner(null);
            }
        }

        return $this;
    }
}
