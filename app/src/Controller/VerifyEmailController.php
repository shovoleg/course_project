<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class VerifyEmailController extends AbstractController
{
    #[Route('/verify/email/{token}', name: 'app_verify_email')]
    public function verify(string $token, UserRepository $users, EntityManagerInterface $em): Response
    {
        $user = $users->findOneBy(['verificationToken' => $token]);
        if (!$user) {
            $this->addFlash('danger', 'verify.invalid');
            return $this->redirectToRoute('app_login');
        }
        if ($user->isVerified()) {
            $this->addFlash('success', 'verify.already');
            return $this->redirectToRoute('app_login');
        }
        if ($user->getVerificationExpiresAt() && $user->getVerificationExpiresAt() < new \DateTimeImmutable()) {
            $this->addFlash('danger', 'verify.expired');
            return $this->redirectToRoute('app_register');
        }
        $user->setVerified(true);
        $user->setVerificationToken(null);
        $user->setVerificationExpiresAt(null);
        $em->flush();
        $this->addFlash('success', 'verify.success');
        return $this->redirectToRoute('app_login');
    }
}
