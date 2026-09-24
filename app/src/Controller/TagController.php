<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\TagRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class TagController extends AbstractController
{
    #[Route('/tags/suggest', name: 'app_tag_suggest')]
    public function suggest(Request $request, TagRepository $tags): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $prefix = trim($request->query->getString('q'));
        if ($prefix === '') {
            return $this->json(['items' => []]);
        }

        return $this->json(['items' => $tags->suggest($prefix)]);
    }
}
