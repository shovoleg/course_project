<?php

declare(strict_types=1);

namespace App\Entity;

enum AttributeType: string
{
    case String = 'string';
    case Text = 'text';
    case Image = 'image';
    case Numeric = 'numeric';
    case Date = 'date';
    case Period = 'period';
    case Boolean = 'boolean';
    case OneOfMany = 'one_of_many';
}
