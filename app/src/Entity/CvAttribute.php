<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\CvAttributeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CvAttributeRepository::class)]
#[ORM\Table(name: 'cv_attribute')]
#[ORM\UniqueConstraint(name: 'uniq_attribute_name', columns: ['name'])]
#[ORM\UniqueConstraint(name: 'uniq_attribute_code', columns: ['code'])]
#[ORM\Index(name: 'ft_attribute', columns: ['name', 'description'], flags: ['fulltext'])]
#[UniqueEntity('name')]
class CvAttribute
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    #[Assert\NotNull]
    private ?AttributeCategory $category = null;

    #[ORM\Column(length: 180)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 180)]
    private string $name = '';

    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\Length(max: 2000)]
    private ?string $description = null;

    #[ORM\Column(length: 32, enumType: AttributeType::class)]
    private AttributeType $type = AttributeType::String;

    #[ORM\Column]
    private bool $builtin = false;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $code = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastUsedAt = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Version]
    #[ORM\Column(type: 'integer')]
    private int $version = 1;

    #[ORM\OneToMany(mappedBy: 'attribute', targetEntity: AttributeOption::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['sortOrder' => 'ASC', 'id' => 'ASC'])]
    private Collection $options;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->options = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCategory(): ?AttributeCategory
    {
        return $this->category;
    }

    public function setCategory(?AttributeCategory $category): void
    {
        $this->category = $category;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = trim($name);
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $description = trim((string) $description);
        $this->description = $description === '' ? null : $description;
    }

    public function getType(): AttributeType
    {
        return $this->type;
    }

    public function setType(AttributeType $type): void
    {
        $this->type = $type;
    }

    public function isBuiltin(): bool
    {
        return $this->builtin;
    }

    public function setBuiltin(bool $builtin): void
    {
        $this->builtin = $builtin;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): void
    {
        $this->code = $code;
    }

    public function getLastUsedAt(): ?\DateTimeImmutable
    {
        return $this->lastUsedAt;
    }

    public function markUsed(): void
    {
        $this->lastUsedAt = new \DateTimeImmutable();
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

    public function getOptions(): Collection
    {
        return $this->options;
    }

    public function addOption(AttributeOption $option): void
    {
        if (!$this->options->contains($option)) {
            $this->options->add($option);
        }
    }

    public function removeOption(AttributeOption $option): void
    {
        $this->options->removeElement($option);
    }
}
