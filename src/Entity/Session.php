<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\SessionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource]
#[ORM\Entity(repositoryClass: SessionRepository::class)]
class Session
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $indexNumber = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(nullable: true)]
    private ?array $objectives = null;

    #[ORM\Column(nullable: true)]
    private ?array $contents = null;

    #[ORM\Column(nullable: true)]
    private ?array $activities = null;

    #[ORM\Column(nullable: true)]
    private ?array $resources = null;

    #[ORM\Column]
    private ?bool $done = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $actualNotes = null;

    #[ORM\Column]
    private ?int $plannedDurationMinutes = null;

    #[ORM\ManyToOne(inversedBy: 'sessions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?CoursePlan $coursePlan = null;

    /**
     * @var Collection<int, Exercise>
     */
    #[ORM\OneToMany(targetEntity: Exercise::class, mappedBy: 'session', orphanRemoval: true)]
    private Collection $exercises;

    public function __construct()
    {
        $this->exercises = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIndexNumber(): ?int
    {
        return $this->indexNumber;
    }

    public function setIndexNumber(int $indexNumber): static
    {
        $this->indexNumber = $indexNumber;

        return $this;
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

    public function getObjectives(): ?array
    {
        return $this->objectives;
    }

    public function setObjectives(?array $objectives): static
    {
        $this->objectives = $objectives;

        return $this;
    }

    public function getContents(): ?array
    {
        return $this->contents;
    }

    public function setContents(?array $contents): static
    {
        $this->contents = $contents;

        return $this;
    }

    public function getActivities(): ?array
    {
        return $this->activities;
    }

    public function setActivities(?array $activities): static
    {
        $this->activities = $activities;

        return $this;
    }

    public function getResources(): ?array
    {
        return $this->resources;
    }

    public function setResources(?array $resources): static
    {
        $this->resources = $resources;

        return $this;
    }

    public function isDone(): ?bool
    {
        return $this->done;
    }

    public function setDone(bool $done): static
    {
        $this->done = $done;

        return $this;
    }

    public function getActualNotes(): ?string
    {
        return $this->actualNotes;
    }

    public function setActualNotes(?string $actualNotes): static
    {
        $this->actualNotes = $actualNotes;

        return $this;
    }

    public function getPlannedDurationMinutes(): ?int
    {
        return $this->plannedDurationMinutes;
    }

    public function setPlannedDurationMinutes(int $plannedDurationMinutes): static
    {
        $this->plannedDurationMinutes = $plannedDurationMinutes;

        return $this;
    }

    public function getCoursePlan(): ?CoursePlan
    {
        return $this->coursePlan;
    }

    public function setCoursePlan(?CoursePlan $coursePlan): static
    {
        $this->coursePlan = $coursePlan;

        return $this;
    }

    /**
     * @return Collection<int, Exercise>
     */
    public function getExercises(): Collection
    {
        return $this->exercises;
    }

    public function addExercise(Exercise $exercise): static
    {
        if (!$this->exercises->contains($exercise)) {
            $this->exercises->add($exercise);
            $exercise->setSession($this);
        }

        return $this;
    }

    public function removeExercise(Exercise $exercise): static
    {
        if ($this->exercises->removeElement($exercise)) {
            // set the owning side to null (unless already changed)
            if ($exercise->getSession() === $this) {
                $exercise->setSession(null);
            }
        }

        return $this;
    }
}
