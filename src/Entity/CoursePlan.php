<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\CoursePlanRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource]
#[ORM\Entity(repositoryClass: CoursePlanRepository::class)]
class CoursePlan
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $generalPlan = null;

    #[ORM\Column(nullable: true)]
    private ?array $evaluationCriteria = null;

    #[ORM\Column]
    private ?int $nbSessionsPlanned = null;

    #[ORM\Column]
    private ?int $expectedTotalHours = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne(inversedBy: 'coursePlan')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Syllabus $syllabus = null;

    #[ORM\ManyToOne(inversedBy: 'coursePlans')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $owner = null;

    #[ORM\OneToMany(mappedBy: 'coursePlan', targetEntity: Session::class, orphanRemoval: true)]
    private Collection $sessions;

    public function __construct()
    {
        $this->sessions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getGeneralPlan(): ?string
    {
        return $this->generalPlan;
    }

    public function setGeneralPlan(string $generalPlan): static
    {
        $this->generalPlan = $generalPlan;

        return $this;
    }

    public function getEvaluationCriteria(): ?array
    {
        return $this->evaluationCriteria;
    }

    public function setEvaluationCriteria(?array $evaluationCriteria): static
    {
        $this->evaluationCriteria = $evaluationCriteria;

        return $this;
    }

    public function getNbSessionsPlanned(): ?int
    {
        return $this->nbSessionsPlanned;
    }

    public function setNbSessionsPlanned(int $nbSessionsPlanned): static
    {
        $this->nbSessionsPlanned = $nbSessionsPlanned;

        return $this;
    }

    public function getExpectedTotalHours(): ?int
    {
        return $this->expectedTotalHours;
    }

    public function setExpectedTotalHours(int $expectedTotalHours): static
    {
        $this->expectedTotalHours = $expectedTotalHours;

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

    public function getSyllabus(): ?Syllabus
    {
        return $this->syllabus;
    }

    public function setSyllabus(?Syllabus $syllabus): static
    {
        $this->syllabus = $syllabus;

        return $this;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): static
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * @return Collection<int, Session>
     */
    public function getSessions(): Collection
    {
        return $this->sessions;
    }

    public function addSession(Session $session): static
    {
        if (!$this->sessions->contains($session)) {
            $this->sessions->add($session);
            $session->setCoursePlan($this);
        }

        return $this;
    }

    public function removeSession(Session $session): static
    {
        if ($this->sessions->removeElement($session)) {
            // set the owning side to null (unless already changed)
            if ($session->getCoursePlan() === $this) {
                $session->setCoursePlan(null);
            }
        }

        return $this;
    }
}
