<?php

namespace App\Entity;

use App\Repository\GradeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GradeRepository::class)]
class Grade
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $name = null;

    /**
     * @var Collection<int, Circuit>
     */
    #[ORM\ManyToMany(targetEntity: Circuit::class, inversedBy: 'grades')]
    private Collection $circuit;

    public function __construct()
    {
        $this->circuit = new ArrayCollection();
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
     * @return Collection<int, Circuit>
     */
    public function getCircuit(): Collection
    {
        return $this->circuit;
    }

    public function addCircuit(Circuit $circuit): static
    {
        if (!$this->circuit->contains($circuit)) {
            $this->circuit->add($circuit);
        }

        return $this;
    }

    public function removeCircuit(Circuit $circuit): static
    {
        $this->circuit->removeElement($circuit);

        return $this;
    }
}
