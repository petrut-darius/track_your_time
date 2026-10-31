<?php

namespace App\Entity;

use App\Enum\NotificationTargetType;
use App\Repository\NotificationRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: NotificationRepository::class)]
class Notification
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["notifications:read"])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'notifications')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $recepient = null;

    #[ORM\Column(length: 20)]
    #[Groups(["notifications:read"])]
    private ?string $type = null;

    #[ORM\Column(length: 50)]
    #[Groups(["notifications:read"])]
    private ?string $content = null;

    #[ORM\Column(nullable: true)]
    #[Groups(["notifications:read"])]
    private ?\DateTimeImmutable $readAt = null;

    #[ORM\Column(nullable: true)]
    #[Groups(["notifications:read"])]
    private ?int $targetId = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRecepient(): ?User
    {
        return $this->recepient;
    }

    public function setRecepient(?User $recepient): static
    {
        $this->recepient = $recepient;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function getReadAt(): ?\DateTimeImmutable
    {
        return $this->readAt;
    }

    public function setReadAt(\DateTimeImmutable $readAt): static
    {
        $this->readAt = $readAt;

        return $this;
    }

    public function getTargetId(): ?int
    {
        return $this->targetId; 
    }

    public function setTargetId(int $targetId): static
    {
        $this->targetId = $targetId;

        return $this;
    }
}
