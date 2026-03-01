<?php

namespace App\Entity;

use App\Entity\ShopProduct;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: 'App\Repository\PromotionRepository')]
#[ORM\HasLifecycleCallbacks]
class Promotion
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 100)]
    #[Assert\NotBlank(message: 'Le nom de la promotion est obligatoire')]
    #[Assert\Length(min: 3, max: 100)]
    private string $name;

    #[ORM\Column(type: 'string', length: 20, unique: true)]
    #[Assert\NotBlank(message: 'Le code de promotion est obligatoire')]
    #[Assert\Length(min: 2, max: 20)]
    #[Assert\Regex(pattern: '/^[A-Z0-9_]+$/', message: 'Le code ne peut contenir que des lettres majuscules, des chiffres et des underscores')]
    private string $code;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\Length(max: 500)]
    private ?string $description = null;

    #[ORM\Column(type: 'string', length: 20)]
    #[Assert\NotBlank(message: 'Le type de promotion est obligatoire')]
    private string $type;

    #[ORM\Column(type: 'decimal', precision: 5, scale: 2)]
    #[Assert\NotBlank(message: 'La valeur de la réduction est obligatoire')]
    #[Assert\Positive(message: 'La valeur doit être positive')]
    #[Assert\Range(min: 0.01, max: 100)]
    private string $value;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $minAmount = null;

    #[ORM\Column(type: 'datetime')]
    #[Assert\NotBlank(message: 'La date de début est obligatoire')]
    private \DateTimeInterface $startDate;

    #[ORM\Column(type: 'datetime')]
    #[Assert\NotBlank(message: 'La date de fin est obligatoire')]
    private \DateTimeInterface $endDate;

    #[ORM\Column(type: 'boolean')]
    private bool $isActive = true;

    #[ORM\Column(type: 'boolean')]
    private bool $isPublic = true;

    #[ORM\Column(type: 'boolean')]
    private bool $applyToAllProducts = false;

    #[ORM\ManyToMany(targetEntity: ShopProduct::class, inversedBy: 'promotions')]
    #[ORM\JoinTable(name: 'promotion_products')]
    private Collection $products;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->products = new ArrayCollection();
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

    // Getters et Setters
    public function getId(): int
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;
        return $this;
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
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

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): self
    {
        $this->value = $value;
        return $this;
    }

    public function getValueAsFloat(): float
    {
        return (float) $this->value;
    }

    public function setValueFromFloat(float $value): self
    {
        $this->value = number_format($value, 2, '.', '');
        return $this;
    }

    public function getMinAmount(): ?string
    {
        return $this->minAmount;
    }

    public function setMinAmount(?string $minAmount): self
    {
        $this->minAmount = $minAmount;
        return $this;
    }

    public function getMinAmountAsFloat(): ?float
    {
        return $this->minAmount ? (float) $this->minAmount : null;
    }

    public function setMinAmountFromFloat(?float $minAmount): self
    {
        $this->minAmount = $minAmount ? number_format($minAmount, 2, '.', '') : null;
        return $this;
    }

    public function getStartDate(): \DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeInterface $startDate): self
    {
        $this->startDate = $startDate;
        return $this;
    }

    public function getEndDate(): \DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(\DateTime $endDate): self
    {
        $this->endDate = $endDate;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function isPublic(): bool
    {
        return $this->isPublic;
    }

    public function setIsPublic(bool $isPublic): self
    {
        $this->isPublic = $isPublic;
        return $this;
    }

    public function isApplyToAllProducts(): bool
    {
        return $this->applyToAllProducts;
    }

    public function setApplyToAllProducts(bool $applyToAllProducts): self
    {
        $this->applyToAllProducts = $applyToAllProducts;
        return $this;
    }

    public function getProducts(): Collection
    {
        return $this->products;
    }

    public function addProduct(ShopProduct $product): self
    {
        if (!$this->products->contains($product)) {
            $this->products->add($product);
            $product->addPromotion($this);
        }
        return $this;
    }

    public function removeProduct(ShopProduct $product): self
    {
        $this->products->removeElement($product);
        $product->removePromotion($this);
        return $this;
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

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    // Méthodes utilitaires
    public function isValid(): bool
    {
        $now = new \DateTime();
        return $this->isActive && 
               $this->startDate <= $now && 
               $this->endDate >= $now;
    }

    public function isApplicableToProduct(ShopProduct $product): bool
    {
        if ($this->applyToAllProducts) {
            return true;
        }
        
        return $this->products->contains($product);
    }

    public function calculateDiscount(string $originalPrice): float
    {
        $originalPriceFloat = (float) $originalPrice;
        $valueFloat = (float) $this->value;
        
        if ($this->type === 'percentage') {
            return $originalPriceFloat * ($valueFloat / 100);
        } elseif ($this->type === 'fixed_amount') {
            return $valueFloat;
        }
        
        return 0.0;
    }

    public function getFinalPrice(string $originalPrice): string
    {
        $discount = $this->calculateDiscount($originalPrice);
        $originalPriceFloat = (float) $originalPrice;
        $finalPrice = max(0, $originalPriceFloat - $discount);
        return number_format($finalPrice, 2, '.', '');
    }

    public function getFormattedValue(): string
    {
        if ($this->type === 'percentage') {
            return '-' . $this->value . '%';
        } elseif ($this->type === 'fixed_amount') {
            return '-' . number_format((float) $this->value, 2, ',', ' ') . ' €';
        }
        
        return '';
    }
}
