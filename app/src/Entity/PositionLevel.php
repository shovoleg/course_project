<?php

declare(strict_types=1);

namespace App\Entity;

enum PositionLevel: string
{
    case Junior = 'junior';
    case Middle = 'middle';
    case Senior = 'senior';
    case CLevel = 'c_level';
}
