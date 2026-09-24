<?php

declare(strict_types=1);

namespace App\Exception;

final class ImageHostNotConfigured extends \RuntimeException
{
    public function __construct(string $detail = '')
    {
        parent::__construct($detail);
    }
}
