<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class ShopOrderItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ShopOrder::class, inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false)]
    private ShopOrder $order;

    #[ORM\ManyToOne(targetEntity: ShopProduct::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ShopProduct $product;

    #[ORM\Column]
    private int $quantity;

    #[ORM\Column]
    private float $price; // prix unitaire au moment de l’achat
    #[ORM\ManyToOne]
private ?Size $size = null;

public function getSize(): ?Size
{
    return $this->size;
}

public function setSize(?Size $size): self
{
    $this->size = $size;
    return $this;
}

    // ===== Getters / Setters =====

    public function getId(): ?int { return $this->id; }

    public function getOrder(): ShopOrder { return $this->order; }
    public function setOrder(ShopOrder $order): self { $this->order = $order; return $this; }

    public function getProduct(): ShopProduct { return $this->product; }
    public function setProduct(ShopProduct $product): self { $this->product = $product; return $this; }

    public function getQuantity(): int { return $this->quantity; }
    public function setQuantity(int $quantity): self { $this->quantity = $quantity; return $this; }

    public function getPrice(): float { return $this->price; }
    public function setPrice(float $price): self { $this->price = $price; return $this; }
}
