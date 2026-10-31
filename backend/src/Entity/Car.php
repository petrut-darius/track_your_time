<?php

namespace App\Entity;

use App\Repository\CarRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: CarRepository::class)]
class Car
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["car:read", "circuit-time:read"])]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    #[Groups(["car:read", "circuit-time:read"])]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(["car:read"])]
    private ?string $story = null;

    #[ORM\Column]
    #[Groups(["car:read"])]
    private ?int $hp = null;

    #[ORM\Column(nullable: true)]
    #[Groups(["car:read"])]
    private ?array $photos = null;

    #[ORM\ManyToOne(inversedBy: 'cars')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(["car:read"])]
    private ?User $user = null;

    /**
     * @var Collection<int, CircuitTime>
     */
    #[ORM\OneToMany(targetEntity: CircuitTime::class, mappedBy: 'car', orphanRemoval: true)]
    private Collection $circuitTimes;

    public function __construct()
    {
        $this->circuitTimes = new ArrayCollection();
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

    public function getStory(): ?string
    {
        return $this->story;
    }

    public function setStory(string $story): static
    {
        $this->story = $story;

        return $this;
    }

    public function getHp(): ?int
    {
        return $this->hp;
    }

    public function setHp(int $hp): static
    {
        $this->hp = $hp;

        return $this;
    }

    public function getPhotos(): ?array
    {
        return $this->photos;
    }

    public function setPhotos(?array $photos): static
    {
        $this->photos = $photos;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return Collection<int, CircuitTime>
     */
    public function getCircuitTimes(): Collection
    {
        return $this->circuitTimes;
    }

    public function addCircuitTime(CircuitTime $circuitTime): static
    {
        if (!$this->circuitTimes->contains($circuitTime)) {
            $this->circuitTimes->add($circuitTime);
            $circuitTime->setCar($this);
        }

        return $this;
    }

    public function removeCircuitTime(CircuitTime $circuitTime): static
    {
        if ($this->circuitTimes->removeElement($circuitTime)) {
            // set the owning side to null (unless already changed)
            if ($circuitTime->getCar() === $this) {
                $circuitTime->setCar(null);
            }
        }

        return $this;
    }
}
