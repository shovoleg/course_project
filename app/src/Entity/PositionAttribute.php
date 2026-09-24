<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'position_attribute')]
#[ORM\UniqueConstraint(name: 'uniq_position_attribute', columns: ['position_id', 'attribute_id'])]
class PositionAttribute
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'positionAttributes')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Position $position;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private CvAttribute $attribute;

    #[ORM\Column]
    private int $sortOrder = 0;

    public function __construct(Position $position, CvAttribute $attribute, int $sortOrder)
    {
        $this->position = $position;
        $this->attribute = $attribute;
        $this->sortOrder = $sortOrder;
        $position->addPositionAttribute($this);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPosition(): Position
    {
        return $this->position;
    }

    public function getAttribute(): CvAttribute
    {
        return $this->attribute;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): void
    {
        $this->sortOrder = $sortOrder;
    }
}
