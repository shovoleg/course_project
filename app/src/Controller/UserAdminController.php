<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Form\UserRoleFormType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class UserAdminController extends AbstractController
{
    #[Route('/admin/users', name: 'app_admin_users')]
    public function index(UserRepository $users): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('admin/users.html.twig', [
            'users' => $users->findDirectory(),
        ]);
    }

    #[Route('/admin/users/{id}/roles', name: 'app_admin_roles', requirements: ['id' => '\d+'])]
    public function roles(User $user, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $form = $this->createForm(UserRoleFormType::class, [
            'candidate' => in_array('ROLE_CANDIDATE', $user->getRoles(), true),
            'recruiter' => in_array('ROLE_RECRUITER', $user->getRoles(), true),
            'admin' => in_array('ROLE_ADMIN', $user->getRoles(), true),
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ((int) $request->request->get('version') !== $user->getVersion()) {
                $this->addFlash('danger', 'error.version_conflict');

                return $this->redirectToRoute('app_admin_roles', ['id' => $user->getId()]);
            }
            $roles = [];
            if ($form->get('candidate')->getData()) {
                $roles[] = 'ROLE_CANDIDATE';
            }
            if ($form->get('recruiter')->getData()) {
                $roles[] = 'ROLE_RECRUITER';
            }
            if ($form->get('admin')->getData()) {
                $roles[] = 'ROLE_ADMIN';
            }
            $user->setRoles($roles);
            try {
                $em->flush();
                $this->addFlash('success', 'flash.roles_saved');
            } catch (OptimisticLockException) {
                $this->addFlash('danger', 'error.version_conflict');
            }

            return $this->redirectToRoute('app_admin_users');
        }

        return $this->render('admin/roles.html.twig', [
            'form' => $form,
            'account' => $user,
        ]);
    }

    #[Route('/admin/users/{id}/block', name: 'app_admin_block', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function block(User $user, Request $request, EntityManagerInterface $em): Response
    {
        return $this->changeBlock($user, $request, $em, true);
    }

    #[Route('/admin/users/{id}/unblock', name: 'app_admin_unblock', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function unblock(User $user, Request $request, EntityManagerInterface $em): Response
    {
        return $this->changeBlock($user, $request, $em, false);
    }

    #[Route('/admin/users/{id}/delete', name: 'app_admin_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(User $user, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $actor = $this->getUser();
        if (!$actor instanceof User || $actor->getId() === $user->getId() || !$this->isCsrfTokenValid('submit', $request->request->getString('_token'))) {
            $this->addFlash('danger', 'error.invalid');

            return $this->redirectToRoute('app_admin_users');
        }
        if ((int) $request->request->get('version') !== $user->getVersion()) {
            $this->addFlash('danger', 'error.version_conflict');

            return $this->redirectToRoute('app_admin_users');
        }
        $em->remove($user);
        try {
            $em->flush();
            $this->addFlash('success', 'flash.deleted');
        } catch (OptimisticLockException) {
            $this->addFlash('danger', 'error.version_conflict');
        }

        return $this->redirectToRoute('app_admin_users');
    }

    private function changeBlock(User $user, Request $request, EntityManagerInterface $em, bool $blocked): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $actor = $this->getUser();
        if (!$actor instanceof User || $actor->getId() === $user->getId() || !$this->isCsrfTokenValid('submit', $request->request->getString('_token'))) {
            $this->addFlash('danger', 'error.invalid');

            return $this->redirectToRoute('app_admin_users');
        }
        if ((int) $request->request->get('version') !== $user->getVersion()) {
            $this->addFlash('danger', 'error.version_conflict');

            return $this->redirectToRoute('app_admin_users');
        }
        $user->setBlocked($blocked);
        try {
            $em->flush();
            $this->addFlash('success', $blocked ? 'flash.blocked' : 'flash.unblocked');
        } catch (OptimisticLockException) {
            $this->addFlash('danger', 'error.version_conflict');
        }

        return $this->redirectToRoute('app_admin_users');
    }
}
