<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class ShopProductImage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ShopProduct::class, inversedBy: "images")]
    #[ORM\JoinColumn(nullable: false)]
    private ?ShopProduct $product = null;

    #[ORM\Column(length: 255)]
    private ?string $filename = null;

    // ===== Getters & Setters =====
    public function getId(): ?int { return $this->id; }
    public function getProduct(): ?ShopProduct { return $this->product; }
    public function setProduct(?ShopProduct $product): self { $this->product = $product; return $this; }
    public function getFilename(): ?string { return $this->filename; }
    public function setFilename(string $filename): self { $this->filename = $filename; return $this; }
}
