<?php

namespace App\DTO;

use App\Repository\CircuitTimeRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

class CircuitTimeDTO
{
    #[ORM\Column()]
    #[Assert\NotBlank()]
    #[Groups(["circuit-time:create"])]
    public ?int $minutes = null;

    #[ORM\Column()]
    #[Assert\NotBlank()]
    #[Groups(["circuit-time:create"])]
    public ?int $seconds = null;
}
