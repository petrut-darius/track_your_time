<?php

namespace App\Entity;

use App\Repository\CircuitTimeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Context;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;

#[ORM\Entity(repositoryClass: CircuitTimeRepository::class)]
class CircuitTime
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'circuitTimes')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(["circuit-time:read"])]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'circuitTimes')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(["car:read"])]
    private ?Circuit $circuit = null;

    #[ORM\Column()]
    #[Groups(["circuit-time:read", "circuit-time:create"])]
    private ?int $minutes = null;

    #[ORM\Column()]
    #[Groups(["circuit-time:read", "circuit-time:create"])]
    private ?int $seconds = null;

    #[ORM\ManyToOne(inversedBy: 'yes')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(["circuit-time:read"])]
    private ?Car $car = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getCircuit(): ?Circuit
    {
        return $this->circuit;
    }

    public function setCircuit(?Circuit $circuit): static
    {
        $this->circuit = $circuit;

        return $this;
    }

    public function getMinutes(): ?string
    {
        return $this->minutes;
    }

    public function setMinutes(string $minutes): static
    {
        $this->minutes = $minutes;

        return $this;
    }

    public function getSeconds(): ?string
    {
        return $this->seconds;
    }

    public function setSeconds(string $seconds): static
    {
        $this->seconds = $seconds;

        return $this;
    }

    public function getCar(): ?Car
    {
        return $this->car;
    }

    public function setCar(?Car $car): static
    {
        $this->car = $car;

        return $this;
    }
}
