<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\CandidateAttributeValueRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CandidateAttributeValueRepository::class)]
#[ORM\Table(name: 'candidate_attribute_value')]
#[ORM\UniqueConstraint(name: 'uniq_value_user_attribute', columns: ['user_id', 'attribute_id'])]
#[ORM\Index(name: 'ft_value', columns: ['string_value', 'text_value'], flags: ['fulltext'])]
class CandidateAttributeValue
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'attributeValues')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private CvAttribute $attribute;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $stringValue = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $textValue = null;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 4, nullable: true)]
    private ?string $numericValue = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $dateValue = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $periodStart = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $periodEnd = null;

    #[ORM\Column(nullable: true)]
    private ?bool $booleanValue = null;

    #[ORM\Column(length: 1024, nullable: true)]
    private ?string $imageUrl = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imagePublicId = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?AttributeOption $option = null;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Version]
    #[ORM\Column(type: 'integer')]
    private int $version = 1;

    public function __construct(User $user, CvAttribute $attribute)
    {
        $this->user = $user;
        $this->attribute = $attribute;
        $this->updatedAt = new \DateTimeImmutable();
        $user->addAttributeValue($this);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getAttribute(): CvAttribute
    {
        return $this->attribute;
    }

    public function getStringValue(): ?string
    {
        return $this->stringValue;
    }

    public function getTextValue(): ?string
    {
        return $this->textValue;
    }

    public function getNumericValue(): ?string
    {
        return $this->numericValue;
    }

    public function getDateValue(): ?\DateTimeImmutable
    {
        return $this->dateValue;
    }

    public function getPeriodStart(): ?\DateTimeImmutable
    {
        return $this->periodStart;
    }

    public function getPeriodEnd(): ?\DateTimeImmutable
    {
        return $this->periodEnd;
    }

    public function getBooleanValue(): ?bool
    {
        return $this->booleanValue;
    }

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function getImagePublicId(): ?string
    {
        return $this->imagePublicId;
    }

    public function getOption(): ?AttributeOption
    {
        return $this->option;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function isEmpty(): bool
    {
        return match ($this->attribute->getType()) {
            AttributeType::String => trim((string) $this->stringValue) === '',
            AttributeType::Text => trim((string) $this->textValue) === '',
            AttributeType::Image => trim((string) $this->imageUrl) === '',
            AttributeType::Numeric => $this->numericValue === null || $this->numericValue === '',
            AttributeType::Date => $this->dateValue === null,
            AttributeType::Period => $this->periodStart === null || $this->periodEnd === null,
            AttributeType::Boolean => $this->booleanValue === null,
            AttributeType::OneOfMany => $this->option === null,
        };
    }

    public function apply(array $input, ?AttributeOption $option): bool
    {
        $before = $this->snapshot();
        match ($this->attribute->getType()) {
            AttributeType::String => $this->stringValue = $this->blank($input['string'] ?? null),
            AttributeType::Text => $this->textValue = $this->blank($input['text'] ?? null),
            AttributeType::Numeric => $this->numericValue = $this->numeric($input['numeric'] ?? null),
            AttributeType::Date => $this->dateValue = $this->date($input['date'] ?? null),
            AttributeType::Period => $this->applyPeriod($input),
            AttributeType::Boolean => $this->booleanValue = $this->boolean($input['boolean'] ?? null),
            AttributeType::Image => $this->applyImage($input),
            AttributeType::OneOfMany => $this->option = $option,
        };
        if ($before === $this->snapshot()) {
            return false;
        }
        $this->updatedAt = new \DateTimeImmutable();

        return true;
    }

    private function snapshot(): array
    {
        return [
            $this->stringValue,
            $this->textValue,
            $this->numericValue,
            $this->dateValue?->format('Y-m-d'),
            $this->periodStart?->format('Y-m-d'),
            $this->periodEnd?->format('Y-m-d'),
            $this->booleanValue,
            $this->imageUrl,
            $this->imagePublicId,
            $this->option?->getId(),
        ];
    }

    private function blank(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function applyPeriod(array $input): void
    {
        $this->periodStart = $this->date($input['periodStart'] ?? null);
        $this->periodEnd = $this->date($input['periodEnd'] ?? null);
    }

    private function applyImage(array $input): void
    {
        $this->imageUrl = $this->blank($input['imageUrl'] ?? null);
        $this->imagePublicId = $this->blank($input['imagePublicId'] ?? null);
    }

    private function numeric(mixed $value): ?string
    {
        $value = $this->blank($value);

        return $value === null ? null : number_format((float) $value, 4, '.', '');
    }

    private function boolean(mixed $value): ?bool
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_bool($value)) {
            return $value;
        }

        return in_array((string) $value, ['1', 'true', 'on'], true);
    }

    private function date(mixed $value): ?\DateTimeImmutable
    {
        $value = $this->blank($value);
        if ($value === null) {
            return null;
        }
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date === false ? null : $date;
    }
}
