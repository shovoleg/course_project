<?php

declare(strict_types=1);

namespace App\Entity;

enum PositionVisibility: string
{
    case Public = 'public';
    case Restricted = 'restricted';
}
