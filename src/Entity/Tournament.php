<?php

namespace App\Entity;

use App\Repository\TournamentRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TournamentRepository::class)]
class Tournament
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 140)]
    #[Assert\NotBlank(message: 'Le nom du tournoi est obligatoire.')]
    #[Assert\Length(max: 140, maxMessage: 'Le nom du tournoi ne doit pas dépasser {{ limit }} caractères.')]
    private ?string $name = null;

    #[ORM\ManyToOne(inversedBy: null)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Veuillez sélectionner un jeu.')]
    private ?Game $game = null;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Assert\NotNull(message: 'La date/heure de début est obligatoire.')]
    private ?\DateTimeImmutable $startAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $checkInAt = null;

    #[ORM\Column]
    #[Assert\Positive(message: 'Le nombre de slots doit être positif.')]
    private int $slots = 8;

    #[ORM\Column(length: 40)]
    #[Assert\NotBlank(message: 'Le format est obligatoire (ex: 5v5, 1v1).')]
    #[Assert\Length(max: 40, maxMessage: 'Le format ne doit pas dépasser {{ limit }} caractères.')]
    private ?string $format = null;

    #[ORM\Column(length: 60)]
    #[Assert\NotBlank(message: 'Le prix est obligatoire (ex: 500DT, points).')]
    #[Assert\Length(max: 60, maxMessage: 'Le prix ne doit pas dépasser {{ limit }} caractères.')]
    private ?string $prize = null;

    #[ORM\Column(length: 60)]
    #[Assert\NotBlank(message: 'Les règles sont obligatoires.')]
    #[Assert\Length(max: 60, maxMessage: 'Les règles ne doivent pas dépasser {{ limit }} caractères.')]
    private ?string $rules = null;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank(message: 'Le statut est obligatoire.')]
    #[Assert\Length(max: 20, maxMessage: 'Le statut ne doit pas dépasser {{ limit }} caractères.')]
    private string $status = 'Scheduled';

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getGame(): ?Game
    {
        return $this->game;
    }

    public function setGame(?Game $game): self
    {
        $this->game = $game;
        return $this;
    }

    public function getStartAt(): ?\DateTimeImmutable
    {
        return $this->startAt;
    }

    public function setStartAt(?\DateTimeImmutable $startAt): self
    {
        $this->startAt = $startAt;
        return $this;
    }

    public function getCheckInAt(): ?\DateTimeImmutable
    {
        return $this->checkInAt;
    }

    public function setCheckInAt(?\DateTimeImmutable $checkInAt): self
    {
        $this->checkInAt = $checkInAt;
        return $this;
    }

    public function getSlots(): int
    {
        return $this->slots;
    }

    public function setSlots(int $slots): self
    {
        $this->slots = $slots;
        return $this;
    }

    public function getFormat(): ?string
    {
        return $this->format;
    }

    public function setFormat(?string $format): self
    {
        $this->format = $format;
        return $this;
    }

    public function getPrize(): ?string
    {
        return $this->prize;
    }

    public function setPrize(?string $prize): self
    {
        $this->prize = $prize;
        return $this;
    }

    public function getRules(): ?string
    {
        return $this->rules;
    }

    public function setRules(?string $rules): self
    {
        $this->rules = $rules;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }
}
