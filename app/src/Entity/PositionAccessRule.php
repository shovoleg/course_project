<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'position_access_rule')]
class PositionAccessRule
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'accessRules')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Position $position;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private CvAttribute $attribute;

    #[ORM\Column(length: 16, enumType: AccessOperator::class)]
    private AccessOperator $operator;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $comparison = null;

    public function __construct(Position $position, CvAttribute $attribute, AccessOperator $operator, ?string $comparison)
    {
        $this->position = $position;
        $this->attribute = $attribute;
        $this->operator = $operator;
        $this->comparison = $comparison;
        $position->addAccessRule($this);
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

    public function getOperator(): AccessOperator
    {
        return $this->operator;
    }

    public function getComparison(): ?string
    {
        return $this->comparison;
    }
}
