<?php

namespace App\Entity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class ShopProduct
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column]
    private float $price;

    #[ORM\Column(length: 20)]
    private string $type; // merch | skin
    #[ORM\Column(type: 'boolean')]
private bool $isActive = true;

public function isActive(): bool
{
    return $this->isActive;
}

public function setIsActive(bool $isActive): self
{
    $this->isActive = $isActive;
    return $this;
}

    #[ORM\ManyToOne(targetEntity: Game::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Game $game = null;

    // ===== Getters & Setters =====

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): self
    {
        $this->price = $price;
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
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
    #[ORM\Column(length: 255, nullable: true)]
private ?string $image = null;

public function getImage(): ?string
{
    return $this->image;
}

public function setImage(?string $image): self
{
    $this->image = $image;
    return $this;
}
    #[ORM\OneToMany(mappedBy: "product", targetEntity: ShopProductImage::class, cascade: ["persist", "remove"])]
private Collection $images;



public function getImages(): Collection
{
    return $this->images;
}

public function addImage(ShopProductImage $image): self
{
    if (!$this->images->contains($image)) {
        $this->images[] = $image;
        $image->setProduct($this);
    }
    return $this;
}

public function removeImage(ShopProductImage $image): self
{
    if ($this->images->removeElement($image)) {
        if ($image->getProduct() === $this) {
            $image->setProduct(null);
        }
    }
    return $this;
}
#[ORM\ManyToMany(targetEntity: Size::class)]
private Collection $sizes;

public function __construct()
{
    $this->images = new ArrayCollection();
    $this->sizes = new ArrayCollection();

}

public function getSizes(): Collection
{
    return $this->sizes;
}

public function addSize(Size $size): static
{
    if (!$this->sizes->contains($size)) {
        $this->sizes->add($size);
    }
    return $this;
}

public function removeSize(Size $size): static
{
    $this->sizes->removeElement($size);
    return $this;
}
}


