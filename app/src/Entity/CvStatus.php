<?php

declare(strict_types=1);

namespace App\Entity;

enum CvStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
}
