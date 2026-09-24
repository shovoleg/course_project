<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\AttributeType;
use App\Entity\CandidateAttributeValue;
use App\Entity\CvAttribute;
use App\Entity\Project;
use App\Entity\User;
use App\Exception\VersionConflict;
use App\Repository\AttributeCategoryRepository;
use App\Repository\CandidateAttributeValueRepository;
use App\Repository\CvAttributeRepository;
use App\Repository\CvRepository;
use App\Repository\ProjectRepository;
use App\Security\ProfileVoter;
use App\Service\AccessEvaluator;
use App\Service\NameResolver;
use App\Service\ProfileSaver;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'app_profile')]
    public function me(): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        return $this->redirectToRoute('app_profile_show', ['id' => $user->getId()]);
    }

    #[Route('/profile/{id}', name: 'app_profile_show', requirements: ['id' => '\d+'])]
    public function show(User $user, CandidateAttributeValueRepository $values, ProjectRepository $projects, CvRepository $cvs, AttributeCategoryRepository $categories, AccessEvaluator $access): Response
    {
        $this->denyAccessUnlessGranted(ProfileVoter::EDIT, $user);
        $projects->removeBlank($user);
        $stored = $values->findForProfile($user);
        $builtin = [];
        $info = [];
        foreach ($stored as $value) {
            if ($value->getAttribute()->isBuiltin()) {
                $builtin[] = $value;
            } else {
                $info[] = $value;
            }
        }
        $cvRows = [];
        $ownerValues = [];
        foreach ($stored as $value) {
            $ownerValues[$value->getAttribute()->getId()] = $value;
        }
        foreach ($cvs->findForOwner($user) as $cv) {
            if ($this->isGranted('ROLE_ADMIN') || $access->canBrowse($cv->getPosition(), $user, $ownerValues)) {
                $cvRows[] = $cv;
            }
        }

        return $this->render('profile/show.html.twig', [
            'profile' => $user,
            'builtin' => $builtin,
            'info' => $info,
            'projects' => $projects->findForOwner($user),
            'cvs' => $cvRows,
            'categories' => $categories->findAllOrdered(),
        ]);
    }

    #[Route('/users/{id}', name: 'app_public_profile', requirements: ['id' => '\d+'])]
    public function publicProfile(User $user, CandidateAttributeValueRepository $values, CvRepository $cvs, NameResolver $names, AccessEvaluator $access): Response
    {
        $this->denyAccessUnlessGranted(ProfileVoter::PUBLIC_VIEW, $user);
        $stored = $values->indexedForUser($user);
        $visible = [];
        foreach ($cvs->findForOwner($user) as $cv) {
            if ($cv->getStatus()->value === 'published' && ($this->isGranted('ROLE_ADMIN') || $access->canBrowse($cv->getPosition(), $user, $stored))) {
                $visible[] = $cv;
            }
        }

        return $this->render('profile/public.html.twig', [
            'profile' => $user,
            'name' => ($names->forUsers([$user])[$user->getId()] ?? $user->getEmail()),
            'photo' => $this->image($stored, 'personal_photo'),
            'location' => $this->text($stored, 'location'),
            'cvs' => $visible,
        ]);
    }

    #[Route('/profile/{id}/autosave', name: 'app_profile_autosave', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function autosave(User $user, Request $request, ProfileSaver $saver, TranslatorInterface $translator): JsonResponse
    {
        $this->denyAccessUnlessGranted(ProfileVoter::EDIT, $user);
        if (!$this->isCsrfTokenValid('submit', (string) $request->headers->get('X-CSRF-TOKEN'))) {
            return $this->json(['error' => 'invalid'], 403);
        }
        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            return $this->json(['error' => 'invalid'], 400);
        }
        try {
            return $this->json($saver->save($user, $payload));
        } catch (VersionConflict) {
            return $this->json(['error' => 'version_conflict', 'message' => $translator->trans('profile.conflict')], 409);
        }
    }

    #[Route('/profile/{id}/attributes', name: 'app_profile_attribute_add', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function addAttribute(User $user, Request $request, CvAttributeRepository $attributes, CandidateAttributeValueRepository $values, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted(ProfileVoter::EDIT, $user);
        if (!$this->isCsrfTokenValid('submit', $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException();
        }
        $attribute = $attributes->find($request->request->getInt('attribute'));
        if (!$attribute instanceof CvAttribute || $attribute->isBuiltin()) {
            $this->addFlash('danger', 'error.invalid');

            return $this->redirectToRoute('app_profile_show', ['id' => $user->getId()]);
        }
        if (!$values->findOneFor($user, (int) $attribute->getId())) {
            $em->persist(new CandidateAttributeValue($user, $attribute));
            $attribute->markUsed();
            $em->flush();
            $this->addFlash('success', 'flash.attribute_added');
        }

        return $this->redirectToRoute('app_profile_show', ['id' => $user->getId()]);
    }

    #[Route('/profile/{id}/attributes/{attributeId}/remove', name: 'app_profile_attribute_remove', methods: ['POST'], requirements: ['id' => '\d+', 'attributeId' => '\d+'])]
    public function removeAttribute(User $user, int $attributeId, Request $request, CandidateAttributeValueRepository $values, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted(ProfileVoter::EDIT, $user);
        if (!$this->isCsrfTokenValid('submit', $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException();
        }
        $value = $values->findOneFor($user, $attributeId);
        if (!$value instanceof CandidateAttributeValue || $value->getAttribute()->isBuiltin()) {
            $this->addFlash('danger', 'error.builtin');

            return $this->redirectToRoute('app_profile_show', ['id' => $user->getId()]);
        }
        if ((int) $request->request->get('version') !== $value->getVersion()) {
            $this->addFlash('danger', 'error.version_conflict');

            return $this->redirectToRoute('app_profile_show', ['id' => $user->getId()]);
        }
        $em->remove($value);
        try {
            $em->flush();
            $this->addFlash('success', 'flash.attribute_removed');
        } catch (OptimisticLockException) {
            $this->addFlash('danger', 'error.version_conflict');
        }

        return $this->redirectToRoute('app_profile_show', ['id' => $user->getId()]);
    }

    #[Route('/profile/{id}/projects', name: 'app_profile_project_add', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function addProject(User $user, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted(ProfileVoter::EDIT, $user);
        if (!$this->isCsrfTokenValid('submit', $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException();
        }
        $project = new Project($user);
        $em->persist($project);
        $em->flush();

        return $this->redirectToRoute('app_profile_show', ['id' => $user->getId()]);
    }

    #[Route('/projects/{id}/delete', name: 'app_project_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function deleteProject(Project $project, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted(ProfileVoter::EDIT, $project->getOwner());
        if (!$this->isCsrfTokenValid('submit', $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException();
        }
        if ((int) $request->request->get('version') !== $project->getVersion()) {
            $this->addFlash('danger', 'error.version_conflict');

            return $this->redirectToRoute('app_profile_show', ['id' => $project->getOwner()->getId()]);
        }
        $ownerId = $project->getOwner()->getId();
        $em->remove($project);
        try {
            $em->flush();
            $this->addFlash('success', 'flash.deleted');
        } catch (OptimisticLockException) {
            $this->addFlash('danger', 'error.version_conflict');
        }

        return $this->redirectToRoute('app_profile_show', ['id' => $ownerId]);
    }

    private function image(array $stored, string $code): ?string
    {
        foreach ($stored as $value) {
            if ($value->getAttribute()->getCode() === $code) {
                return $value->getImageUrl();
            }
        }

        return null;
    }

    private function text(array $stored, string $code): ?string
    {
        foreach ($stored as $value) {
            if ($value->getAttribute()->getCode() === $code) {
                return $value->getStringValue();
            }
        }

        return null;
    }
}
