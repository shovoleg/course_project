<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Repository\CandidateAttributeValueRepository;
use App\Service\AccessEvaluator;
use App\Service\StatisticsService;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(StatisticsService $statistics, AccessEvaluator $access, CandidateAttributeValueRepository $values): Response
    {
        $user = $this->getUser();
        $stored = $user instanceof User && !$this->isGranted('ROLE_RECRUITER') ? $values->indexedForUser($user) : [];

        return $this->render('home/index.html.twig', $statistics->home($user instanceof User ? $user : null, $access, $stored));
    }

    #[Route('/health', name: 'app_health')]
    public function health(Connection $connection): Response
    {
        $connection->executeQuery('SELECT 1');

        return new Response('ok');
    }
}
