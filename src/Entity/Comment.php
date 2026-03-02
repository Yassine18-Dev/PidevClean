<?php

// src/Entity/Comment.php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity]
class Comment
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type:"integer")]
    /** @phpstan-ignore-next-line */
    private $id;

    #[ORM\ManyToOne(targetEntity:Post::class, inversedBy:"comments")]
    #[ORM\JoinColumn(nullable:false)]
    private $post;

    #[ORM\ManyToOne(targetEntity:User::class)]
    #[ORM\JoinColumn(nullable:false)]
    private $author;

    #[ORM\Column(type:"text")]
    private $content;

    #[ORM\Column(type:"datetime")]
    private $createdAt;

    #[ORM\Column(type:"string", length:255, nullable:true)]
    private $image;

    #[ORM\ManyToOne(targetEntity:Comment::class)]
    private $parent;

    public function getId(): ?int { return $this->id; }
    public function getPost(): ?Post { return $this->post; }
    public function setPost(Post $post): self { $this->post = $post; return $this; }
    public function getAuthor(): ?User { return $this->author; }
    public function setAuthor(User $author): self { $this->author = $author; return $this; }
    public function getContent(): ?string { return $this->content; }
    public function setContent(string $content): self { $this->content = $content; return $this; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function setCreatedAt(\DateTimeInterface $createdAt): self { $this->createdAt = $createdAt; return $this; }
    public function getImage(): ?string { return $this->image; }
    public function setImage(?string $image): self { $this->image = $image; return $this; }
    public function getParent(): ?Comment { return $this->parent; }
    public function setParent(?Comment $parent): self { $this->parent = $parent; return $this; }
}
