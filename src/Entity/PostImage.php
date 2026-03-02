<?php

// src/Entity/PostImage.php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class PostImage
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type:"integer")]
    /** @phpstan-ignore-next-line */
    private $id;

    #[ORM\Column(type:"string", length:255)]
    private $filename;

    #[ORM\ManyToOne(targetEntity:Post::class, inversedBy:"images")]
    #[ORM\JoinColumn(nullable:false)]
    private ?Post $post = null;

    public function getId(): ?int { return $this->id; }
    public function getFilename(): ?string { return $this->filename; }
    public function setFilename(string $filename): self { $this->filename = $filename; return $this; }
    public function getPost(): ?Post { return $this->post; }
    public function setPost(Post $post): self { $this->post = $post; return $this; }
}
