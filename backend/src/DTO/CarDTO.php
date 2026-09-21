<?php


namespace App\DTO;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;
class CarDTO
{
    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(groups: ["car:create"])]
    #[Assert\Length(min: 4, max:50, groups: ["car:create"])]
    #[Groups(["car:create"])]
    public ?string $name = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(groups: ["car:create"])]
    #[Assert\Length(min: 50, max:2000, groups: ["car:create"])]
    #[Groups(["car:create"])]
    public ?string $story = null;

    #[ORM\Column]
    #[Assert\NotBlank(groups: ["car:create"])]
    #[Assert\Positive(groups: ["car:create"])]
    #[Groups(["car:create"])]
    public ?int $hp = null;

    public ?array $photos = null;
}