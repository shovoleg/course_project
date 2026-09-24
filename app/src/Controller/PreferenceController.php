<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PreferenceController extends AbstractController
{
    #[Route('/locale/{locale}', name: 'app_locale', requirements: ['locale' => 'en|ru'])]
    public function locale(string $locale, Request $request, EntityManagerInterface $em): Response
    {
        $request->getSession()->set('_locale', $locale);
        $user = $this->getUser();
        if ($user instanceof User) {
            $user->setLocale($locale);
            $em->flush();
        }

        return $this->redirect($request->headers->get('referer', $this->generateUrl('app_home')));
    }

    #[Route('/theme', name: 'app_theme', methods: ['POST'])]
    public function theme(Request $request, EntityManagerInterface $em): Response
    {
        $theme = $request->request->getString('theme');
        if (!in_array($theme, ['light', 'dark'], true) || !$this->isCsrfTokenValid('submit', $request->request->getString('_token'))) {
            return $this->redirectToRoute('app_home');
        }
        $request->getSession()->set('theme', $theme);
        $user = $this->getUser();
        if ($user instanceof User) {
            $user->setTheme($theme);
            $em->flush();
        }

        return $this->redirect($request->headers->get('referer', $this->generateUrl('app_home')));
    }
}
