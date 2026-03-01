<?php

namespace App\Entity;

use App\Repository\TeamRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TeamRepository::class)]
#[ORM\Table(name: 'team')]
class Team
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $name = '';

    // ✅ DB checkpoint a slug (nullable + unique)
    #[ORM\Column(length: 140, unique: true, nullable: true)]
    private ?string $slug = null;

    // ✅ DB checkpoint a owner_id (nullable)
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'owner_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?User $owner = null;

    // ✅ DB checkpoint a updated_at (nullable)
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    // ✅ exigences Team/Player : game + logo + maxPlayers
    #[ORM\Column(length: 20, options: ['default' => 'lol'])]
    private string $game = 'lol';

    #[ORM\Column(name: 'logo_name', length: 255, nullable: true)]
    private ?string $logoName = null;

    #[ORM\Column(name: 'banner_name', length: 255, nullable: true)]
    private ?string $bannerName = null;

    #[ORM\Column(name: 'max_players', type: 'integer', options: ['default' => 5])]
    private int $maxPlayers = 5;

    #[ORM\OneToMany(mappedBy: 'team', targetEntity: Player::class)]
    private Collection $players;

    #[ORM\OneToMany(mappedBy: 'team', targetEntity: Invitation::class, orphanRemoval: true, cascade: ['remove'])]
    private Collection $invitations;

    public function __construct()
    {
        $this->players = new ArrayCollection();
        $this->invitations = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): self { $this->name = $name; return $this; }

    public function getSlug(): ?string { return $this->slug; }
    public function setSlug(?string $slug): self { $this->slug = $slug; return $this; }

    public function getOwner(): ?User { return $this->owner; }
    public function setOwner(?User $owner): self { $this->owner = $owner; return $this; }

    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }
    public function setUpdatedAt(?\DateTimeInterface $updatedAt): self { $this->updatedAt = $updatedAt; return $this; }

    public function getGame(): string { return $this->game; }
    public function setGame(string $game): self { $this->game = $game; return $this; }

    public function getLogoName(): ?string { return $this->logoName; }
    public function setLogoName(?string $logoName): self { $this->logoName = $logoName; return $this; }

    public function getBannerName(): ?string { return $this->bannerName; }
    public function setBannerName(?string $bannerName): self { $this->bannerName = $bannerName; return $this; }

    public function getMaxPlayers(): int { return $this->maxPlayers; }
    public function setMaxPlayers(int $maxPlayers): self { $this->maxPlayers = $maxPlayers; return $this; }

    /** @return Collection<int, Player> */
    public function getPlayers(): Collection { return $this->players; }

    public function addPlayer(Player $player): self
    {
        if (!$this->players->contains($player)) {
            $this->players->add($player);
            $player->setTeam($this);
        }
        return $this;
    }

    public function removePlayer(Player $player): self
    {
        if ($this->players->removeElement($player)) {
            if ($player->getTeam() === $this) {
                $player->setTeam(null);
            }
        }
        return $this;
    }

    /** @return Collection<int, Invitation> */
    public function getInvitations(): Collection { return $this->invitations; }

    public function addInvitation(Invitation $invitation): self
    {
        if (!$this->invitations->contains($invitation)) {
            $this->invitations->add($invitation);
            $invitation->setTeam($this);
        }
        return $this;
    }

    public function removeInvitation(Invitation $invitation): self
    {
        if ($this->invitations->removeElement($invitation)) {
            if ($invitation->getTeam() === $this) {
                $invitation->setTeam(null);
            }
        }
        return $this;
    }

    // ✅ exigence : count(players) < max_players
    public function hasAvailableSlot(): bool
    {
        return $this->players->count() < $this->maxPlayers;
    }

    // ✅ exigence : pas inviter si déjà membre / déjà invité (pending)
    public function canInvite(Player $player): bool
    {
        // déjà membre
        if ($player->getTeam() && $player->getTeam()->getId() === $this->getId()) {
            return false;
        }

        // déjà invité (pending) par cette team
        foreach ($this->invitations as $inv) {
            if ($inv->getPlayer()?->getId() === $player->getId() && $inv->getStatus() === 'pending') {
                return false;
            }
        }

        return true;
    }
}