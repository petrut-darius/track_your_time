<?php

namespace App\DTO;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

class ProfileUpdateDTO
{
    #[Groups(['user:update'])]
    #[Assert\NotBlank]
    #[Assert\Email]
    public ?string $email = null;

    #[Groups(['user:update'])]
    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 30)]
    public ?string $username = null;

    #[Groups(['user:update'])]
    #[Assert\NotBlank]
    public ?string $firstName = null;

    #[Groups(['user:update'])]
    #[Assert\NotBlank]
    public ?string $lastName = null;

    public ?UploadedFile $avatar = null;
}