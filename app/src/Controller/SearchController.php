<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Repository\CvRepository;
use App\Service\NameResolver;
use App\Service\SearchService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SearchController extends AbstractController
{
    #[Route('/search', name: 'app_search')]
    public function index(Request $request, SearchService $search, CvRepository $cvs, NameResolver $names): Response
    {
        $query = $request->query->getString('q');
        $user = $this->getUser();
        $result = $search->search($query, $user instanceof User ? $user : null);
        $ids = array_map(static fn ($cv) => (int) $cv->getId(), $result['cvs']);
        $owners = array_map(static fn ($cv) => $cv->getOwner(), $result['cvs']);

        return $this->render('search/index.html.twig', [
            'query' => $query,
            'positions' => $result['positions'],
            'cvs' => $result['cvs'],
            'likes' => $cvs->likeCounts($ids),
            'names' => $names->forUsers($owners),
        ]);
    }
}
