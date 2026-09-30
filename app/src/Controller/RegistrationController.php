<?php

declare(strict_types=1);

namespace App\Controller;

use App\Form\RegistrationFormType;
use App\Service\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

final class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserFactory $users, UserPasswordHasherInterface $hasher, EntityManagerInterface $em, \Symfony\Component\Mailer\MailerInterface $mailer, \Symfony\Component\Routing\Generator\UrlGeneratorInterface $urls): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }
        $user = $users->create('', ['ROLE_CANDIDATE'], null);
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword($hasher->hashPassword($user, (string) $form->get('plainPassword')->getData()));
            $user->setVerified(false);
            $user->setVerificationToken(bin2hex(random_bytes(32)));
            $user->setVerificationExpiresAt(new \DateTimeImmutable('+24 hours'));
            $em->persist($user);
            $em->flush();
            $verifyUrl = $urls->generate('app_verify_email', ['token' => $user->getVerificationToken()], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL);
            try {
                $email = (new \Symfony\Component\Mime\Email())
                    ->from('noreply@' . parse_url($verifyUrl, PHP_URL_HOST))
                    ->to($user->getEmail())
                    ->subject('Confirm your email')
                    ->html('<p><a href="' . $verifyUrl . '">Confirm email</a> — valid 24h. If you did not register, ignore.</p><p>' . $verifyUrl . '</p>');
                $mailer->send($email);
            } catch (\Throwable $e) {
            }
            $this->addFlash('success', 'flash.registered_verify');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/register.html.twig', [
            'form' => $form,
        ]);
    }
}
