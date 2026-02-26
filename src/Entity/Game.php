<?php

namespace App\Entity;

use App\Repository\GameRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: GameRepository::class)]
#[ORM\Table(name: 'game')]
class Game
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 50)]
    #[Assert\NotBlank(message: "Le nom du jeu est obligatoire.")]
    #[Assert\Length(
        min: 2,
        max: 50,
        minMessage: "Le nom du jeu doit contenir au moins {{ limit }} caractères.",
        maxMessage: "Le nom du jeu ne doit pas dépasser {{ limit }} caractères."
    )]
    private ?string $name = null;

    #[ORM\Column(name: 'max_players', type: 'integer')]
    #[Assert\NotBlank(message: "Le nombre max de joueurs est obligatoire.")]
    #[Assert\Positive(message: "Le nombre max de joueurs doit être > 0.")]
    #[Assert\LessThanOrEqual(value: 100, message: "Le nombre max de joueurs ne doit pas dépasser 100.")]
    private ?int $maxPlayers = null;

    #[ORM\Column(name: 'image_url', type: 'string', length: 255)]
    #[Assert\NotBlank(message: "L'URL de l'image est obligatoire.")]
    #[Assert\Url(message: "Veuillez saisir une URL valide (ex: https://...).")]
    private ?string $imageUrl = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getMaxPlayers(): ?int
    {
        return $this->maxPlayers;
    }

    public function setMaxPlayers(int $maxPlayers): self
    {
        $this->maxPlayers = $maxPlayers;
        return $this;
    }

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function setImageUrl(string $imageUrl): self
    {
        $this->imageUrl = $imageUrl;
        return $this;
    }

    public function __toString(): string
    {
        return (string) ($this->name ?? 'Game');
    }
}