<?php

namespace App\Entity;

use App\Repository\PlayerRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PlayerRepository::class)]
#[ORM\Table(name: 'player')]
class Player
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    /**
     * ✅ DB: player.user_id est UNIQUE => relation 1-1 logique
     * => côté User : #[ORM\OneToOne(mappedBy:'user', ...)] private ?Player $player
     */
    #[ORM\OneToOne(targetEntity: User::class, inversedBy: 'player')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?User $user = null;

    // ✅ DB checkpoint: team_id nullable (SET NULL)
    #[ORM\ManyToOne(targetEntity: Team::class, inversedBy: 'players')]
    #[ORM\JoinColumn(name: 'team_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Team $team = null;

    // ✅ Module Team/Player (exigences)
    #[ORM\Column(length: 80)]
    private string $nickname = '';

    #[ORM\Column(length: 20, options: ['default' => 'lol'])]
    private string $game = 'lol';

    // ✅ On le mappe car Doctrine le demandait (ADD rank)
    #[ORM\Column(length: 30, nullable: true)]
    private ?string $rank = null;

    #[ORM\Column(name: 'avatar_name', length: 255, nullable: true)]
    private ?string $avatarName = null;

    /**
     * ✅ Champs existants en DB checkpoint (pour stopper Doctrine qui veut DROP)
     */
    #[ORM\Column(length: 255)]
    private string $name = '';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $photo = null;

    #[ORM\Column(name: 'discord_id', length: 64, nullable: true)]
    private ?string $discordId = null;

    #[ORM\Column(name: 'discord_username', length: 64, nullable: true)]
    private ?string $discordUsername = null;

    #[ORM\Column(name: 'discord_avatar_url', length: 255, nullable: true)]
    private ?string $discordAvatarUrl = null;

    #[ORM\Column(name: 'discord_linked_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $discordLinkedAt = null;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(name: 'riot_puuid', length: 120, nullable: true)]
    private ?string $riotPuuid = null;

    #[ORM\Column(name: 'riot_game_name', length: 60, nullable: true)]
    private ?string $riotGameName = null;

    #[ORM\Column(name: 'riot_tag_line', length: 10, nullable: true)]
    private ?string $riotTagLine = null;

    #[ORM\Column(name: 'riot_rank', length: 30, nullable: true)]
    private ?string $riotRank = null;

    #[ORM\Column(name: 'riot_points', type: 'integer', nullable: true)]
    private ?int $riotPoints = null;

    // ✅ Invitations (reçues + envoyées)
    #[ORM\OneToMany(mappedBy: 'player', targetEntity: Invitation::class, orphanRemoval: true, cascade: ['remove'])]
    private Collection $receivedInvitations;

    #[ORM\OneToMany(mappedBy: 'invitedBy', targetEntity: Invitation::class, orphanRemoval: true, cascade: ['remove'])]
    private Collection $sentInvitations;

    public function __construct()
    {
        $this->receivedInvitations = new ArrayCollection();
        $this->sentInvitations = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): self { $this->user = $user; return $this; }

    public function getTeam(): ?Team { return $this->team; }
    public function setTeam(?Team $team): self { $this->team = $team; return $this; }

    public function getNickname(): string { return $this->nickname; }
    public function setNickname(string $nickname): self { $this->nickname = $nickname; return $this; }

    public function getGame(): string { return $this->game; }
    public function setGame(string $game): self { $this->game = $game; return $this; }

    public function getRank(): ?string { return $this->rank; }
    public function setRank(?string $rank): self { $this->rank = $rank; return $this; }

    public function getAvatarName(): ?string { return $this->avatarName; }
    public function setAvatarName(?string $avatarName): self { $this->avatarName = $avatarName; return $this; }

    // ---- Legacy getters/setters (minimum utile) ----
    public function getName(): string { return $this->name; }
    public function setName(string $name): self { $this->name = $name; return $this; }

    public function getPhoto(): ?string { return $this->photo; }
    public function setPhoto(?string $photo): self { $this->photo = $photo; return $this; }

    public function getDiscordId(): ?string { return $this->discordId; }
    public function setDiscordId(?string $discordId): self { $this->discordId = $discordId; return $this; }

    public function getDiscordUsername(): ?string { return $this->discordUsername; }
    public function setDiscordUsername(?string $discordUsername): self { $this->discordUsername = $discordUsername; return $this; }

    public function getDiscordAvatarUrl(): ?string { return $this->discordAvatarUrl; }
    public function setDiscordAvatarUrl(?string $discordAvatarUrl): self { $this->discordAvatarUrl = $discordAvatarUrl; return $this; }

    public function getDiscordLinkedAt(): ?\DateTimeInterface { return $this->discordLinkedAt; }
    public function setDiscordLinkedAt(?\DateTimeInterface $discordLinkedAt): self { $this->discordLinkedAt = $discordLinkedAt; return $this; }

    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }
    public function setUpdatedAt(?\DateTimeInterface $updatedAt): self { $this->updatedAt = $updatedAt; return $this; }

    public function getRiotPuuid(): ?string { return $this->riotPuuid; }
    public function setRiotPuuid(?string $riotPuuid): self { $this->riotPuuid = $riotPuuid; return $this; }

    public function getRiotGameName(): ?string { return $this->riotGameName; }
    public function setRiotGameName(?string $riotGameName): self { $this->riotGameName = $riotGameName; return $this; }

    public function getRiotTagLine(): ?string { return $this->riotTagLine; }
    public function setRiotTagLine(?string $riotTagLine): self { $this->riotTagLine = $riotTagLine; return $this; }

    public function getRiotRank(): ?string { return $this->riotRank; }
    public function setRiotRank(?string $riotRank): self { $this->riotRank = $riotRank; return $this; }

    public function getRiotPoints(): ?int { return $this->riotPoints; }
    public function setRiotPoints(?int $riotPoints): self { $this->riotPoints = $riotPoints; return $this; }

    /** @return Collection<int, Invitation> */
    public function getReceivedInvitations(): Collection { return $this->receivedInvitations; }

    /** @return Collection<int, Invitation> */
    public function getSentInvitations(): Collection { return $this->sentInvitations; }
}