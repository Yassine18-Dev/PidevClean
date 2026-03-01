<?php

namespace App\Entity;

use App\Repository\PlayerRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PlayerRepository::class)]
class Player
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Relation attendue par User#player
     */
    #[ORM\OneToOne(targetEntity: User::class, inversedBy: 'player')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    /**
     * Relation attendue par Team#players
     */
    #[ORM\ManyToOne(targetEntity: Team::class, inversedBy: 'players')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Team $team = null;

    /**
     * Invitations reçues (Invitation#player)
     * @var Collection<int, Invitation>
     */
    #[ORM\OneToMany(mappedBy: 'player', targetEntity: Invitation::class, orphanRemoval: true)]
    private Collection $receivedInvitations;

    /**
     * Invitations envoyées (Invitation#invitedBy)
     * @var Collection<int, Invitation>
     */
    #[ORM\OneToMany(mappedBy: 'invitedBy', targetEntity: Invitation::class)]
    private Collection $sentInvitations;

    // --------------------------
    // Champs Player (profil)
    // --------------------------

    #[ORM\Column(length: 80)]
    private string $nickname = '';

    #[ORM\Column(name: 'avatar_name', length: 255, nullable: true)]
    private ?string $avatarName = null;

    // ✅ IMPORTANT : on garde ces 2 colonnes car elles existent déjà en DB
    // sinon Doctrine propose DROP name/photo
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $photo = null;

    // Discord
    #[ORM\Column(name: 'discord_id', length: 64, nullable: true)]
    private ?string $discordId = null;

    #[ORM\Column(name: 'discord_username', length: 64, nullable: true)]
    private ?string $discordUsername = null;

    #[ORM\Column(name: 'discord_avatar_url', length: 255, nullable: true)]
    private ?string $discordAvatarUrl = null;

    // ✅ FIX: hash avatar Discord (colonne DB: discord_avatar)
    #[ORM\Column(name: 'discord_avatar', length: 64, nullable: true)]
    private ?string $discordAvatar = null;

    #[ORM\Column(name: 'discord_linked_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $discordLinkedAt = null;

    // Riot
    #[ORM\Column(name: 'riot_puuid', length: 120, nullable: true)]
    private ?string $riotPuuid = null;

    #[ORM\Column(name: 'riot_game_name', length: 60, nullable: true)]
    private ?string $riotGameName = null;

    #[ORM\Column(name: 'riot_tag_line', length: 10, nullable: true)]
    private ?string $riotTagLine = null;

    #[ORM\Column(name: 'riot_rank', length: 30, nullable: true)]
    private ?string $riotRank = null;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->receivedInvitations = new ArrayCollection();
        $this->sentInvitations = new ArrayCollection();
    }

    // --------------------------
    // Getters / Setters relations
    // --------------------------

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getTeam(): ?Team
    {
        return $this->team;
    }

    public function setTeam(?Team $team): self
    {
        $this->team = $team;
        return $this;
    }

    /**
     * @return Collection<int, Invitation>
     */
    public function getReceivedInvitations(): Collection
    {
        return $this->receivedInvitations;
    }

    public function addReceivedInvitation(Invitation $invitation): self
    {
        if (!$this->receivedInvitations->contains($invitation)) {
            $this->receivedInvitations->add($invitation);
            $invitation->setPlayer($this);
        }
        return $this;
    }

    public function removeReceivedInvitation(Invitation $invitation): self
    {
        if ($this->receivedInvitations->removeElement($invitation)) {
            if ($invitation->getPlayer() === $this) {
                $invitation->setPlayer(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, Invitation>
     */
    public function getSentInvitations(): Collection
    {
        return $this->sentInvitations;
    }

    public function addSentInvitation(Invitation $invitation): self
    {
        if (!$this->sentInvitations->contains($invitation)) {
            $this->sentInvitations->add($invitation);
            $invitation->setInvitedBy($this);
        }
        return $this;
    }

    public function removeSentInvitation(Invitation $invitation): self
    {
        if ($this->sentInvitations->removeElement($invitation)) {
            if ($invitation->getInvitedBy() === $this) {
                $invitation->setInvitedBy(null);
            }
        }
        return $this;
    }

    // --------------------------
    // Getters / Setters profil
    // --------------------------

    public function getNickname(): string
    {
        return $this->nickname;
    }

    public function setNickname(string $nickname): self
    {
        $this->nickname = $nickname;
        return $this;
    }

    public function getAvatarName(): ?string
    {
        return $this->avatarName;
    }

    public function setAvatarName(?string $avatarName): self
    {
        $this->avatarName = $avatarName;
        return $this;
    }

    // ✅ added to avoid DROP
    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;
        return $this;
    }

    // ✅ added to avoid DROP
    public function getPhoto(): ?string
    {
        return $this->photo;
    }

    public function setPhoto(?string $photo): self
    {
        $this->photo = $photo;
        return $this;
    }

    public function getDiscordId(): ?string
    {
        return $this->discordId;
    }

    public function setDiscordId(?string $discordId): self
    {
        $this->discordId = $discordId;
        return $this;
    }

    public function getDiscordUsername(): ?string
    {
        return $this->discordUsername;
    }

    public function setDiscordUsername(?string $discordUsername): self
    {
        $this->discordUsername = $discordUsername;
        return $this;
    }

    public function getDiscordAvatarUrl(): ?string
    {
        return $this->discordAvatarUrl;
    }

    public function setDiscordAvatarUrl(?string $discordAvatarUrl): self
    {
        $this->discordAvatarUrl = $discordAvatarUrl;
        return $this;
    }

    public function getDiscordAvatar(): ?string
    {
        return $this->discordAvatar;
    }

    public function setDiscordAvatar(?string $discordAvatar): self
    {
        $this->discordAvatar = $discordAvatar;
        return $this;
    }

    public function getDiscordLinkedAt(): ?\DateTimeInterface
    {
        return $this->discordLinkedAt;
    }

    public function setDiscordLinkedAt(?\DateTimeInterface $discordLinkedAt): self
    {
        $this->discordLinkedAt = $discordLinkedAt;
        return $this;
    }

    public function getRiotPuuid(): ?string
    {
        return $this->riotPuuid;
    }

    public function setRiotPuuid(?string $riotPuuid): self
    {
        $this->riotPuuid = $riotPuuid;
        return $this;
    }

    public function getRiotGameName(): ?string
    {
        return $this->riotGameName;
    }

    public function setRiotGameName(?string $riotGameName): self
    {
        $this->riotGameName = $riotGameName;
        return $this;
    }

    public function getRiotTagLine(): ?string
    {
        return $this->riotTagLine;
    }

    public function setRiotTagLine(?string $riotTagLine): self
    {
        $this->riotTagLine = $riotTagLine;
        return $this;
    }

    public function getRiotRank(): ?string
    {
        return $this->riotRank;
    }

    public function setRiotRank(?string $riotRank): self
    {
        $this->riotRank = $riotRank;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }
}