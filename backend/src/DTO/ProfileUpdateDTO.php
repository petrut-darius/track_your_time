<?php

namespace App\DTO;

use App\Validator\UniqueEmailConstraint;
use App\Validator\UniqueUsernameConstraint;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

class ProfileUpdateDTO
{
    #[Groups(['user:update'])]
    #[Assert\Email(mode: Assert\Email::VALIDATION_MODE_STRICT, groups: ["user:update"])]
    #[UniqueEmailConstraint(groups: ["user:update"])]
    public ?string $email = null;

    #[Groups(['user:update'])]
    #[Assert\Length(min: 3, max: 30, groups: ["user:update"])]
    #[UniqueUsernameConstraint(groups: ["user:update"])]
    public ?string $username = null;

    #[Groups(['user:update'])]
    #[SerializedName("first_name")]
    #[Assert\Length(max: 255, groups: ["user:update"])]
    public ?string $firstName = null;

    #[Groups(['user:update'])]
    #[SerializedName("last_name")]
    #[Assert\Length(max: 255, groups: ["user:update"])]
    public ?string $lastName = null;

    public ?UploadedFile $avatar = null;
}