<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\PositionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PositionRepository::class)]
#[ORM\Table(name: 'job_position')]
#[ORM\Index(name: 'idx_position_updated', columns: ['updated_at'])]
#[ORM\Index(name: 'ft_position', columns: ['title', 'short_description', 'company'], flags: ['fulltext'])]
class Position
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 180)]
    private string $title = '';

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank]
    #[Assert\Length(max: 4000)]
    private string $shortDescription = '';

    #[ORM\Column(length: 180, nullable: true)]
    #[Assert\Length(max: 180)]
    private ?string $company = null;

    #[ORM\Column(length: 16, nullable: true, enumType: PositionLevel::class)]
    private ?PositionLevel $level = null;

    #[ORM\Column(length: 16, enumType: PositionVisibility::class)]
    private PositionVisibility $visibility = PositionVisibility::Public;

    #[ORM\Column]
    #[Assert\PositiveOrZero]
    #[Assert\LessThanOrEqual(50)]
    private int $maxProjects = 3;

    #[ORM\Column(length: 64, nullable: true, unique: true)]
    private ?string $apiToken = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Version]
    #[ORM\Column(type: 'integer')]
    private int $version = 1;

    #[ORM\OneToMany(mappedBy: 'position', targetEntity: PositionAttribute::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['sortOrder' => 'ASC', 'id' => 'ASC'])]
    private Collection $positionAttributes;

    #[ORM\OneToMany(mappedBy: 'position', targetEntity: PositionAccessRule::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $accessRules;

    #[ORM\ManyToMany(targetEntity: Tag::class)]
    #[ORM\JoinTable(name: 'position_tag')]
    #[ORM\JoinColumn(name: 'position_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'tag_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private Collection $projectTags;

    #[ORM\OneToMany(mappedBy: 'position', targetEntity: Cv::class, cascade: ['remove'], orphanRemoval: true)]
    private Collection $cvs;

    #[ORM\OneToMany(mappedBy: 'position', targetEntity: DiscussionPost::class, cascade: ['remove'], orphanRemoval: true)]
    private Collection $posts;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->positionAttributes = new ArrayCollection();
        $this->accessRules = new ArrayCollection();
        $this->projectTags = new ArrayCollection();
        $this->cvs = new ArrayCollection();
        $this->posts = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = trim($title);
    }

    public function getShortDescription(): string
    {
        return $this->shortDescription;
    }

    public function setShortDescription(string $shortDescription): void
    {
        $this->shortDescription = trim($shortDescription);
    }

    public function getCompany(): ?string
    {
        return $this->company;
    }

    public function setCompany(?string $company): void
    {
        $company = trim((string) $company);
        $this->company = $company === '' ? null : $company;
    }

    public function getLevel(): ?PositionLevel
    {
        return $this->level;
    }

    public function setLevel(?PositionLevel $level): void
    {
        $this->level = $level;
    }

    public function getVisibility(): PositionVisibility
    {
        return $this->visibility;
    }

    public function setVisibility(PositionVisibility $visibility): void
    {
        $this->visibility = $visibility;
    }

    public function getMaxProjects(): int
    {
        return $this->maxProjects;
    }

    public function setMaxProjects(int $maxProjects): void
    {
        $this->maxProjects = $maxProjects;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function getPositionAttributes(): Collection
    {
        return $this->positionAttributes;
    }

    public function addPositionAttribute(PositionAttribute $link): void
    {
        if (!$this->positionAttributes->contains($link)) {
            $this->positionAttributes->add($link);
        }
    }

    public function removePositionAttribute(PositionAttribute $link): void
    {
        $this->positionAttributes->removeElement($link);
    }

    public function getAccessRules(): Collection
    {
        return $this->accessRules;
    }

    public function addAccessRule(PositionAccessRule $rule): void
    {
        if (!$this->accessRules->contains($rule)) {
            $this->accessRules->add($rule);
        }
    }

    public function removeAccessRule(PositionAccessRule $rule): void
    {
        $this->accessRules->removeElement($rule);
    }

    public function getProjectTags(): Collection
    {
        return $this->projectTags;
    }

    public function replaceProjectTags(array $tags): void
    {
        $this->projectTags->clear();
        foreach ($tags as $tag) {
            $this->projectTags->add($tag);
        }
    }

    public function getCvs(): Collection
    {
        return $this->cvs;
    }

    public function getPosts(): Collection
    {
        return $this->posts;
    }

    public function getApiToken(): ?string
    {
        return $this->apiToken;
    }

    public function setApiToken(?string $apiToken): void
    {
        $this->apiToken = $apiToken;
    }

    public function ensureApiToken(): string
    {
        if ($this->apiToken === null || $this->apiToken === '') {
            $this->apiToken = bin2hex(random_bytes(32));
        }
        return $this->apiToken;
    }
}
