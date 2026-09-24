<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'attribute_option')]
class AttributeOption
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'options')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private CvAttribute $attribute;

    #[ORM\Column(length: 180)]
    private string $label;

    #[ORM\Column]
    private int $sortOrder = 0;

    public function __construct(CvAttribute $attribute, string $label)
    {
        $this->attribute = $attribute;
        $this->label = $label;
        $attribute->addOption($this);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAttribute(): CvAttribute
    {
        return $this->attribute;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): void
    {
        $this->label = $label;
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
