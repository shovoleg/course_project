<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\CvRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CvRepository::class)]
#[ORM\Table(name: 'cv')]
#[ORM\UniqueConstraint(name: 'uniq_cv_owner_position', columns: ['owner_id', 'position_id'])]
#[ORM\Index(name: 'idx_cv_created', columns: ['created_at'])]
class Cv
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'cvs')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $owner;

    #[ORM\ManyToOne(inversedBy: 'cvs')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Position $position;

    #[ORM\Column(length: 16, enumType: CvStatus::class)]
    private CvStatus $status = CvStatus::Draft;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Version]
    #[ORM\Column(type: 'integer')]
    private int $version = 1;

    #[ORM\OneToMany(mappedBy: 'cv', targetEntity: CvLike::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $likes;

    public function __construct(User $owner, Position $position)
    {
        $this->owner = $owner;
        $this->position = $position;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->likes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOwner(): User
    {
        return $this->owner;
    }

    public function getPosition(): Position
    {
        return $this->position;
    }

    public function getStatus(): CvStatus
    {
        return $this->status;
    }

    public function setStatus(CvStatus $status): void
    {
        $this->status = $status;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function getLikes(): Collection
    {
        return $this->likes;
    }
}
