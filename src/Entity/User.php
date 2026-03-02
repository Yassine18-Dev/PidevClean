<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[UniqueEntity(fields: ['email'], message: 'Email already used.')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_SUSPENDED = 'SUSPENDED';
    public const STATUS_BANNED = 'BANNED';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Assert\NotBlank(message: 'Email is required.')]
    #[Assert\Email(message: 'Invalid email.')]
    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    #[Assert\NotBlank(message: 'Username is required.')]
    #[Assert\Length(min: 3, max: 50, minMessage: 'Username must be at least 3 chars.')]
    #[ORM\Column(length: 50)]
    private ?string $username = null;

    #[Assert\Choice(choices: ['PLAYER','CAPTAIN','FAN'], message: 'Invalid roleType.')]
    #[ORM\Column(length: 20)]
    private string $roleType = 'PLAYER';

    #[Assert\Choice(choices: [self::STATUS_ACTIVE, self::STATUS_SUSPENDED, self::STATUS_BANNED], message: 'Invalid status.')]
    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_ACTIVE;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $bio = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $favoriteGame = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastActivityAt = null;

    // ✅ Reset password dans la même entité
    #[ORM\Column(length: 64, nullable: true)]
    private ?string $resetToken = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $resetExpiresAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getEmail(): ?string { return $this->email; }
    public function setEmail(string $email): static { $this->email = $email; return $this; }

    public function getUsername(): ?string { return $this->username; }
    public function setUsername(string $username): static { $this->username = $username; return $this; }

    public function getRoleType(): string { return $this->roleType; }
    public function setRoleType(string $roleType): static { $this->roleType = $roleType; return $this; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): static { $this->status = $status; return $this; }

    public function getBio(): ?string { return $this->bio; }
    public function setBio(?string $bio): static { $this->bio = $bio; return $this; }

    public function getFavoriteGame(): ?string { return $this->favoriteGame; }
    public function setFavoriteGame(?string $favoriteGame): static { $this->favoriteGame = $favoriteGame; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function getLastActivityAt(): ?\DateTimeImmutable { return $this->lastActivityAt; }
    public function setLastActivityAt(?\DateTimeImmutable $d): static { $this->lastActivityAt = $d; return $this; }

    public function getResetToken(): ?string { return $this->resetToken; }
    public function setResetToken(?string $token): static { $this->resetToken = $token; return $this; }

    public function getResetExpiresAt(): ?\DateTimeImmutable { return $this->resetExpiresAt; }
    public function setResetExpiresAt(?\DateTimeImmutable $d): static { $this->resetExpiresAt = $d; return $this; }

    public function isResetTokenValid(string $token): bool
    {
        if (!$this->resetToken || !$this->resetExpiresAt) return false;
        if (!hash_equals($this->resetToken, $token)) return false;
        return $this->resetExpiresAt->getTimestamp() > time();
    }

    // Symfony Security
    public function getUserIdentifier(): string { return (string) $this->email; }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_values(array_unique($roles));
    }

    public function setRoles(array $roles): static { $this->roles = $roles; return $this; }

    public function getPassword(): string { return (string) $this->password; }
    public function setPassword(string $password): static { $this->password = $password; return $this; }

    public function eraseCredentials(): void {}
}
