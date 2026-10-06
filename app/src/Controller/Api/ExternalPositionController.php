<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Repository\PositionRepository;
use App\Service\PositionAggregationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class ExternalPositionController extends AbstractController
{
    #[Route('/api/external/position/{token}', name: 'api_external_position', methods: ['GET'])]
    public function show(string $token, PositionRepository $positions, PositionAggregationService $agg): JsonResponse
    {
        $pos = $positions->findOneBy(['apiToken' => $token]);
        if (!$pos) {
            return new JsonResponse(['error' => 'invalid token'], 404);
        }
        $data = $agg->aggregate($pos);
        $data['token'] = $token;
        $data['generatedAt'] = (new \DateTimeImmutable())->format('c');
        return new JsonResponse($data);
    }

    #[Route('/api/external/ping', name: 'api_external_ping', methods: ['GET'])]
    public function ping(): JsonResponse
    {
        return new JsonResponse(['ok' => true, 'time' => (new \DateTimeImmutable())->format('c')]);
    }
}
