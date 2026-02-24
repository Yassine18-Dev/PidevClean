<?php

namespace App\Entity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class ShopProduct
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le nom du produit est obligatoire')]
    #[Assert\Length(min: 3, max: 255, minMessage: 'Le nom doit faire au moins 3 caractères', maxMessage: 'Le nom ne peut pas dépasser 255 caractères')]
    private string $name;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'Le prix est obligatoire')]
    #[Assert\Positive(message: 'Le prix doit être un nombre positif')]
    #[Assert\LessThan(value: 10000, message: 'Le prix ne peut pas dépasser 10 000 €')]
    private float $price;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank(message: 'Le type est obligatoire')]
    #[Assert\Choice(choices: ['skin', 'merch'], message: 'Le type doit être "skin" ou "merch"')]
    private string $type; // merch | skin

    #[ORM\Column]
    private bool $isActive = true;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

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
    #[Assert\Count(min: 1, minMessage: 'Au moins une taille doit être sélectionnée pour les produits merch', groups: ['merch_validation'])]
    private Collection $sizes;

public function __construct()
    {
        $this->images = new ArrayCollection();
        $this->sizes = new ArrayCollection();
        $this->promotions = new ArrayCollection();
        $this->createdAt = new \DateTime();
    }

    #[ORM\PrePersist]
    public function onPrePersist()
    {
        $this->createdAt = new \DateTime();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate()
    {
        $this->updatedAt = new \DateTime();
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

#[Assert\Callback]
public function validateSizes(ExecutionContextInterface $context): void
{
    if ($this->type === 'merch' && $this->sizes->isEmpty()) {
        $context->buildViolation('Au moins une taille doit être sélectionnée pour les produits merch')
            ->atPath('sizes')
            ->addViolation();
    }
}

// Gestion des promotions
#[ORM\ManyToMany(targetEntity: Promotion::class, mappedBy: 'products')]
private Collection $promotions;

public function getPromotions(): Collection
{
    return $this->promotions;
}

public function addPromotion(Promotion $promotion): self
{
    if (!$this->promotions->contains($promotion)) {
        $this->promotions->add($promotion);
    }
    return $this;
}

public function removePromotion(Promotion $promotion): self
{
    $this->promotions->removeElement($promotion);
    return $this;
}

public function getActivePromotions(): Collection
{
    return $this->promotions->filter(function(Promotion $promotion) {
        return $promotion->isValid();
    });
}

public function getBestPromotion(): ?Promotion
{
    $activePromotions = $this->getActivePromotions();
    
    if ($activePromotions->isEmpty()) {
        return null;
    }
    
    // Retourner la promotion avec le plus grand rabais
    return $activePromotions->reduce(function(?Promotion $best, Promotion $promotion) {
        if ($best === null || $promotion->calculateDiscount($this->price) > $best->calculateDiscount($this->price)) {
            return $promotion;
        }
        return $best;
    });
}

public function getFinalPrice(): float
{
    $bestPromotion = $this->getBestPromotion();
    
    if ($bestPromotion) {
        return $bestPromotion->getFinalPrice($this->price);
    }
    
    return $this->price;
}

public function getDiscountAmount(): float
{
    $bestPromotion = $this->getBestPromotion();
    
    if ($bestPromotion) {
        return $bestPromotion->calculateDiscount($this->price);
    }
    
    return 0.0;
}

public function getFormattedDiscount(): string
{
    $bestPromotion = $this->getBestPromotion();
    
    if ($bestPromotion) {
        return $bestPromotion->getFormattedValue();
    }
    
    return '';
}

public function getCreatedAt(): \DateTimeInterface
{
    return $this->createdAt;
}

public function setCreatedAt(\DateTimeInterface $createdAt): self
{
    $this->createdAt = $createdAt;
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

public function getIsActive(): bool
{
    return $this->isActive;
}

public function setIsActive(bool $isActive): self
{
    $this->isActive = $isActive;
    return $this;
}

public function hasActivePromotion(): bool
{
    return !$this->getActivePromotions()->isEmpty();
}
}


