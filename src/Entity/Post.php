<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity]
class Post
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type:"integer")]
    /** @phpstan-ignore-next-line */
    private $id;

    #[ORM\ManyToOne(targetEntity:User::class)]
    #[ORM\JoinColumn(nullable:false)]
    private $author;

    #[ORM\Column(type:"text")]
    private $content;

    #[ORM\Column(type:"datetime")]
    private $createdAt;

    #[ORM\OneToMany(mappedBy:"post", targetEntity:PostImage::class, cascade:["persist","remove"])]
    private $images;

    #[ORM\ManyToMany(targetEntity:User::class)]
    private $likes;

    #[ORM\OneToMany(mappedBy:"post", targetEntity:Comment::class, cascade:["remove"])]
    private $comments;


    #[ORM\Column(type:"string", length:20)]
    private $status = 'APPROVED';

    #[ORM\Column(type:"text", nullable:true)]
    private $summary;

    #[ORM\Column(type:"string", length:255, nullable:true)]
    private $subject;

    public function __construct() {
        $this->images = new ArrayCollection();
        $this->likes = new ArrayCollection();
        $this->comments = new ArrayCollection();
    }

    // Getters et setters
    public function getId(): ?int { return $this->id; }
    public function getAuthor(): ?User { return $this->author; }
    public function setAuthor(User $author): self { $this->author = $author; return $this; }
    public function getContent(): ?string { return $this->content; }
    public function setContent(string $content): self { $this->content = $content; return $this; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function setCreatedAt(\DateTimeInterface $createdAt): self { $this->createdAt = $createdAt; return $this; }
    public function getImages(): Collection { return $this->images; }
public function addImage(PostImage $image): self {
    if (!$this->images->contains($image)) {
        $this->images[] = $image;
        $image->setPost($this);
    }
    return $this;
}
    public function getLikes(): Collection { return $this->likes; }
    public function addLike(User $user): self { if (!$this->likes->contains($user)) $this->likes[] = $user; return $this; }
    public function getComments(): Collection { return $this->comments; }

    public function getStatus(): ?string { return $this->status; }
    public function setStatus(string $status): self { $this->status = $status; return $this; }

    public function getSummary(): ?string { return $this->summary; }
    public function setSummary(?string $summary): self { $this->summary = $summary; return $this; }

    public function getSubject(): ?string { return $this->subject; }
    public function setSubject(?string $subject): self { $this->subject = $subject; return $this; }
}
