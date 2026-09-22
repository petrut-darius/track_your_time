<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\PasswordStrength;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[UniqueEntity(["username", "email"], groups: ["user:update"])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["user:read", "car:read"])]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    #[Groups(["friendship:read", "user:update"])]
    #[Assert\NotBlank(groups: ['registration'])]
    #[Assert\Email(mode: Assert\Email::VALIDATION_MODE_STRICT, groups: ['registration', "user:update"])]
    #[Assert\Length(max: 180, groups: ['registration'])]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    #[SerializedName("plain_password")]
    #[Assert\NotBlank(groups: ['registration'])]
    #[Assert\PasswordStrength(minScore: PasswordStrength::STRENGTH_WEAK, groups: ['registration'])]
    #[Assert\Length(max: 255, min: 8, groups: ['registration'])]
    private ?string $plainPassword = null;
    public function setPlainPassword(?string $plainPassword): static
    {
        $this->plainPassword = $plainPassword;
        return $this;
    }

    public function getPlainPassword(): string|null
    {
        return $this->plainPassword;
    }

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    #[Assert\NotBlank()]
    private ?string $password = null;

    #[ORM\Column(length: 30, unique: true)]
    #[Groups(["user:read", "friendship:read", "user:update", "car:read"])]
    #[Assert\NotBlank(groups: ['registration', "user:update"])]
    #[Assert\Length(max: 30, groups: ['registration', "user:update"])]
    private ?string $username = null;

    #[ORM\Column(length: 255)]
    #[Groups(["user:read", "friendship:read", "user:update", "car:read"])]
    #[Assert\NotBlank(groups: ['registration', "user:update"])]
    #[Assert\Length(max: 255, groups: ['registration', "user:update"])]
    private ?string $last_name = null;

    #[ORM\Column(length: 255)]
    #[Groups(["user:read", "friendship:read", "user:update", "car:read"])]
    #[Assert\NotBlank(groups: ['registration', "user:update"])]
    #[Assert\Length(max: 255, groups: ['registration', "user:update"])]
    private ?string $first_name = null;

    /**
     * @var Collection<int, Car>
     */
    #[ORM\OneToMany(targetEntity: Car::class, mappedBy: 'user')]
    private Collection $cars;

    #[ORM\Column(nullable: true)]
    #[Groups(["user:read", "friendship:read"])]
    private ?string $avatar = null;

    /**
     * @var Collection<int, CircuitTime>
     */
    #[ORM\OneToMany(targetEntity: CircuitTime::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $circuitTimes;

    /**
     * @var Collection<int, Friendship>
     */
    #[ORM\OneToMany(targetEntity: Friendship::class, mappedBy: 'user')]
    private Collection $sentFriendships;

    #[ORM\OneToMany(targetEntity: Friendship::class, mappedBy: 'friend')]
    private Collection $receivedFriendships;

    public function __construct()
    {
        $this->cars = new ArrayCollection();
        $this->circuitTimes = new ArrayCollection();
        $this->sentFriendships = new ArrayCollection();
        $this->receivedFriendships = new ArrayCollection();    
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Ensure the session doesn't contain actual password hashes by CRC32C-hashing them, as supported since Symfony 7.3.
     */
    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0".self::class."\0password"] = hash('crc32c', $this->password);

        return $data;
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
        // @deprecated, to be removed when upgrading to Symfony 8
    }

    #[Groups("circuit-time:read")]
    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->last_name;
    }

    public function setLastName(string $last_name): static
    {
        $this->last_name = $last_name;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->first_name;
    }

    public function setFirstName(string $first_name): static
    {
        $this->first_name = $first_name;

        return $this;
    }

    /**
     * @return Collection<int, Car>
     */
    public function getCars(): Collection
    {
        return $this->cars;
    }

    public function addCar(Car $car): static
    {
        if (!$this->cars->contains($car)) {
            $this->cars->add($car);
            $car->setUser($this);
        }

        return $this;
    }

    public function removeCar(Car $car): static
    {
        if ($this->cars->removeElement($car)) {
            // set the owning side to null (unless already changed)
            if ($car->getUser() === $this) {
                $car->setUser(null);
            }
        }

        return $this;
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(?string $avatar): static
    {
        $this->avatar = $avatar;

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
            $circuitTime->setUser($this);
        }

        return $this;
    }

    public function removeCircuitTime(CircuitTime $circuitTime): static
    {
        if ($this->circuitTimes->removeElement($circuitTime)) {
            // set the owning side to null (unless already changed)
            if ($circuitTime->getUser() === $this) {
                $circuitTime->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Friendship>
     */
    public function getSentFriendships(): Collection
    {
        return $this->sentFriendships;
    }

    /**
     * @return Collection<int, Friendship>
     */
    public function getReceivedFriendships(): Collection
    {
        return $this->receivedFriendships;
    }

    public function addSentFriendship(Friendship $friendship): static
    {
        if (!$this->sentFriendships->contains($friendship)) {
            $this->sentFriendships->add($friendship);
            $friendship->setUser($this);
        }

        return $this;
    }

    public function removeSentFriendship(Friendship $friendship): static
    {
        if ($this->sentFriendships->removeElement($friendship)) {
            if ($friendship->getUser() === $this) {
                $friendship->setUser(null);
            }
        }

        return $this;
    }
}