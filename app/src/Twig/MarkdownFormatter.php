<?php

declare(strict_types=1);

namespace App\Twig;

use League\CommonMark\CommonMarkConverter;
use Twig\Attribute\AsTwigFilter;

final class MarkdownFormatter
{
    private CommonMarkConverter $converter;

    public function __construct()
    {
        $this->converter = new CommonMarkConverter([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
    }

    #[AsTwigFilter('md', isSafe: ['html'])]
    public function format(?string $markdown): string
    {
        return $this->converter->convert($markdown ?? '')->getContent();
    }
}
