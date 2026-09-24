<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Cv;
use App\Entity\CvLike;
use App\Entity\CvStatus;
use App\Entity\Position;
use App\Entity\User;
use App\Repository\CvRepository;
use App\Security\CvVoter;
use App\Security\PositionVoter;
use App\Service\CvAssembler;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CvController extends AbstractController
{
    #[Route('/positions/{id}/cvs', name: 'app_cv_create', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function create(Position $position, Request $request, EntityManagerInterface $em, CvRepository $cvs): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }
        $this->denyAccessUnlessGranted(PositionVoter::VIEW, $position);
        if (!$this->isGranted('ROLE_CANDIDATE') && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }
        if (!$this->isCsrfTokenValid('submit', $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException();
        }
        $owner = $user;
        if ($this->isGranted('ROLE_ADMIN') && $request->request->getInt('owner') > 0) {
            $selected = $em->getRepository(User::class)->find($request->request->getInt('owner'));
            if ($selected instanceof User) {
                $owner = $selected;
            }
        }
        $existing = $cvs->findOneFor($owner, $position);
        if ($existing instanceof Cv) {
            return $this->redirectToRoute('app_cv_show', ['id' => $existing->getId()]);
        }
        $cv = new Cv($owner, $position);
        $em->persist($cv);
        $em->flush();
        $this->addFlash('success', 'flash.created');

        return $this->redirectToRoute('app_cv_show', ['id' => $cv->getId()]);
    }

    #[Route('/cvs/{id}', name: 'app_cv_show', requirements: ['id' => '\d+'])]
    public function show(Cv $cv, CvAssembler $assembler, CvRepository $cvs): Response
    {
        $this->denyAccessUnlessGranted(CvVoter::VIEW, $cv);
        $cv = $cvs->findForDocument((int) $cv->getId()) ?? $cv;
        $user = $this->getUser();
        $liked = false;
        if ($user instanceof User) {
            $liked = in_array((int) $cv->getId(), $cvs->likedBy($user, [(int) $cv->getId()]), true);
        }

        return $this->render('cv/show.html.twig', [
            'cv' => $cv,
            'document' => $assembler->present($cv),
            'likes' => $cvs->likeCounts([(int) $cv->getId()])[(int) $cv->getId()] ?? 0,
            'liked' => $liked,
        ]);
    }

    #[Route('/cvs/{id}/publish', name: 'app_cv_publish', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function publish(Cv $cv, Request $request, CvAssembler $assembler, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted(CvVoter::EDIT, $cv);
        if (!$this->isCsrfTokenValid('submit', $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException();
        }
        if ((int) $request->request->get('version') !== $cv->getVersion()) {
            $this->addFlash('danger', 'error.version_conflict');

            return $this->redirectToRoute('app_cv_show', ['id' => $cv->getId()]);
        }
        $document = $assembler->present($cv);
        if (!$document['complete']) {
            $this->addFlash('danger', 'error.not_complete');

            return $this->redirectToRoute('app_cv_show', ['id' => $cv->getId()]);
        }
        $cv->setStatus(CvStatus::Published);
        try {
            $em->flush();
            $this->addFlash('success', 'flash.published');
        } catch (OptimisticLockException) {
            $this->addFlash('danger', 'error.version_conflict');
        }

        return $this->redirectToRoute('app_cv_show', ['id' => $cv->getId()]);
    }

    #[Route('/cvs/{id}/delete', name: 'app_cv_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Cv $cv, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted(CvVoter::EDIT, $cv);
        if (!$this->isCsrfTokenValid('submit', $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException();
        }
        if ((int) $request->request->get('version') !== $cv->getVersion()) {
            $this->addFlash('danger', 'error.version_conflict');

            return $this->redirectToRoute('app_cv_show', ['id' => $cv->getId()]);
        }
        $positionId = $cv->getPosition()->getId();
        $em->remove($cv);
        try {
            $em->flush();
            $this->addFlash('success', 'flash.deleted');
        } catch (OptimisticLockException) {
            $this->addFlash('danger', 'error.version_conflict');

            return $this->redirectToRoute('app_cv_show', ['id' => $cv->getId()]);
        }

        return $this->redirectToRoute('app_position_show', ['id' => $positionId]);
    }

    #[Route('/cvs/{id}/like', name: 'app_cv_like', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function like(Cv $cv, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted(CvVoter::LIKE, $cv);
        $user = $this->getUser();
        if (!$user instanceof User || !$this->isCsrfTokenValid('submit', $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException();
        }
        $existing = $em->getRepository(CvLike::class)->findOneBy(['cv' => $cv, 'recruiter' => $user]);
        if ($existing instanceof CvLike) {
            $em->remove($existing);
            $this->addFlash('success', 'flash.unliked');
        } else {
            $em->persist(new CvLike($cv, $user));
            $this->addFlash('success', 'flash.liked');
        }
        $em->flush();

        return $this->redirect($request->headers->get('referer', $this->generateUrl('app_cv_show', ['id' => $cv->getId()])));
    }
}
