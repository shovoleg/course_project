<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\CvRepository;
use App\Repository\PositionRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class QrPdfController extends AbstractController
{
    #[Route('/export/qr', name: 'app_export_qr')]
    public function qr(PositionRepository $positions, CvRepository $cvs, UserRepository $users, UrlGeneratorInterface $urls): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $allPositions = $positions->findBy([], ['updatedAt' => 'DESC']);
        $allCvs = $cvs->findBy([], ['updatedAt' => 'DESC'], 50);
        $allUsers = $users->findBy([], ['id' => 'ASC'], 50);
        $base = $urls->generate('app_home', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $host = parse_url($base, PHP_URL_SCHEME) . '://' . parse_url($base, PHP_URL_HOST);
        if (str_contains($host, 'localhost') || str_contains($host, '127.0.0.1')) {
            $host = $_ENV['DEFAULT_URI'] ?? $host;
        }
        return $this->render('export/qr.html.twig', [
            'positions' => $allPositions,
            'cvs' => $allCvs,
            'users' => $allUsers,
            'host' => rtrim($host, '/'),
        ]);
    }
}
