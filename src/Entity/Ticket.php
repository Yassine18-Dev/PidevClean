<?php

namespace App\Entity;

use App\Repository\TicketRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TicketRepository::class)]
class Ticket
{
    public const STATUS_PENDING     = 'pending';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_RESOLVED    = 'resolved';
    public const STATUS_REJECTED    = 'rejected';

    public const PRIORITY_LOW      = 'low';
    public const PRIORITY_MEDIUM   = 'medium';
    public const PRIORITY_HIGH     = 'high';
    public const PRIORITY_CRITICAL = 'critical';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Assert\NotBlank(message: 'Subject is required.')]
    #[Assert\Length(min: 5, max: 255)]
    #[ORM\Column(length: 255)]
    private ?string $subject = null;

    #[Assert\NotBlank(message: 'Description is required.')]
    #[Assert\Length(min: 10)]
    #[ORM\Column(type: 'text')]
    private ?string $description = null;

    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column(length: 20)]
    private string $priority = self::PRIORITY_MEDIUM;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $adminNotes = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $submitter = null;

    #[ORM\ManyToOne(targetEntity: TicketCategory::class, inversedBy: 'tickets')]
    #[ORM\JoinColumn(nullable: false)]
    private ?TicketCategory $category = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getSubject(): ?string { return $this->subject; }
    public function setSubject(string $subject): static { $this->subject = $subject; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(string $description): static { $this->description = $description; return $this; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): static { $this->status = $status; return $this; }

    public function getPriority(): string { return $this->priority; }
    public function setPriority(string $priority): static { $this->priority = $priority; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $createdAt): static { $this->createdAt = $createdAt; return $this; }

    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }
    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static { $this->updatedAt = $updatedAt; return $this; }

    public function getAdminNotes(): ?string { return $this->adminNotes; }
    public function setAdminNotes(?string $adminNotes): static { $this->adminNotes = $adminNotes; $this->updatedAt = new \DateTimeImmutable(); return $this; }

    public function getSubmitter(): ?User { return $this->submitter; }
    public function setSubmitter(?User $submitter): static { $this->submitter = $submitter; return $this; }

    public function getCategory(): ?TicketCategory { return $this->category; }
    public function setCategory(?TicketCategory $category): static { $this->category = $category; return $this; }

    public function getStatusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING     => 'Pending',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_RESOLVED    => 'Resolved',
            self::STATUS_REJECTED    => 'Rejected',
            default                  => ucfirst($this->status),
        };
    }

    public function getPriorityLabel(): string
    {
        return ucfirst($this->priority);
    }
}