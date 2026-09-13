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
    private ?Circuit $circuit = null;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    #[Groups(["circuit-time:read"])]
    #[Context([DateTimeNormalizer::FORMAT_KEY => "H:i:s"])]
    private ?\DateTime $time = null;

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

    public function getTime(): ?\DateTime
    {
        return $this->time;
    }

    public function setTime(\DateTime $time): static
    {
        $this->time = $time;

        return $this;
    }
}
