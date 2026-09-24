<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Position;
use App\Entity\PositionLevel;
use App\Entity\User;
use App\Form\DiscussionFormType;
use App\Form\PositionFormType;
use App\Repository\AttributeCategoryRepository;
use App\Repository\CandidateAttributeValueRepository;
use App\Repository\CvAttributeRepository;
use App\Repository\CvRepository;
use App\Repository\DiscussionPostRepository;
use App\Repository\PositionRepository;
use App\Security\PositionVoter;
use App\Service\AccessEvaluator;
use App\Service\NameResolver;
use App\Service\PositionEditor;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

final class PositionController extends AbstractController
{
    #[Route('/positions', name: 'app_position_index')]
    public function index(Request $request, PositionRepository $positions, AccessEvaluator $access, CandidateAttributeValueRepository $values): Response
    {
        $level = $request->query->getString('level');
        $tag = $request->query->getString('tag');
        $sort = $request->query->getString('sort', 'updated');
        $direction = $request->query->getString('dir', 'DESC');
        $rows = $positions->rows($level !== '' ? $level : null, $tag !== '' ? $tag : null, $sort, $direction);
        if (!$this->isGranted('ROLE_RECRUITER')) {
            $user = $this->getUser();
            $stored = $user instanceof User ? $values->indexedForUser($user) : [];
            $allowed = [];
            foreach ($positions->findAllWithRules() as $position) {
                if ($access->canBrowse($position, $user instanceof User ? $user : null, $stored)) {
                    $allowed[$position->getId()] = true;
                }
            }
            $rows = array_values(array_filter($rows, static fn (array $row): bool => isset($allowed[(int) $row['id']])));
        }

        return $this->render('position/index.html.twig', [
            'rows' => $rows,
            'level' => $level,
            'tag' => $tag,
            'sort' => $sort,
            'dir' => $direction,
            'levels' => PositionLevel::cases(),
        ]);
    }

    #[Route('/positions/new', name: 'app_position_new')]
    public function new(Request $request, EntityManagerInterface $em, PositionEditor $editor, CvAttributeRepository $attributes, AttributeCategoryRepository $categories): Response
    {
        $this->denyAccessUnlessGranted('ROLE_RECRUITER');
        $position = new Position();
        $form = $this->createForm(PositionFormType::class, $position);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $editor->sync($position, $request->request->all('attribute_ids'), $request->request->all('tags'), $request->request->all('rules'));
            $em->persist($position);
            $em->flush();
            $this->addFlash('success', 'flash.created');

            return $this->redirectToRoute('app_position_show', ['id' => $position->getId()]);
        }

        return $this->render('position/form.html.twig', [
            'form' => $form,
            'position' => $position,
            'categories' => $categories->findAllOrdered(),
            'catalog' => $this->catalog($attributes),
        ]);
    }

    #[Route('/positions/{id}', name: 'app_position_show', requirements: ['id' => '\d+'])]
    public function show(Position $position, PositionRepository $positions, CvRepository $cvs, CandidateAttributeValueRepository $values, DiscussionPostRepository $posts, NameResolver $names, AccessEvaluator $access): Response
    {
        $position = $positions->findOneDetailed((int) $position->getId()) ?? $position;
        $this->denyAccessUnlessGranted(PositionVoter::VIEW, $position);
        $user = $this->getUser();
        $ownCv = $user instanceof User ? $cvs->findOneFor($user, $position) : null;
        $list = [];
        $likeCounts = [];
        $ownerNames = [];
        $averages = [];
        if ($this->isGranted('ROLE_RECRUITER')) {
            $all = $cvs->findForPosition($position);
            $ownerIds = [];
            $ruleIds = [];
            foreach ($position->getAccessRules() as $rule) {
                $ruleIds[] = (int) $rule->getAttribute()->getId();
            }
            foreach ($all as $cv) {
                $ownerIds[] = (int) $cv->getOwner()->getId();
            }
            $grouped = $values->indexedForUsers($ownerIds, $ruleIds);
            foreach ($all as $cv) {
                $ownerValues = $grouped[$cv->getOwner()->getId()] ?? [];
                if ($access->canBrowse($position, $cv->getOwner(), $ownerValues) || $this->isGranted('ROLE_ADMIN')) {
                    $list[] = $cv;
                }
            }
            $visibleIds = array_map(static fn ($cv) => (int) $cv->getId(), $list);
            $likeCounts = $cvs->likeCounts($visibleIds);
            $ownerNames = $names->forUsers(array_map(static fn ($cv) => $cv->getOwner(), $list));
            $numericIds = [];
            foreach ($position->getPositionAttributes() as $link) {
                if ($link->getAttribute()->getType()->value === 'numeric') {
                    $numericIds[] = (int) $link->getAttribute()->getId();
                }
            }
            $visibleOwners = array_map(static fn ($cv) => (int) $cv->getOwner()->getId(), $list);
            $averages = $values->numericForUsers($visibleOwners, $numericIds);
        }
        $discussion = $posts->forPosition($position);
        $authorNames = $names->forUsers(array_map(static fn ($post) => $post->getAuthor(), $discussion));
        $canCreate = $user instanceof User && !$ownCv && $this->isGranted(PositionVoter::VIEW, $position) && ($this->isGranted('ROLE_CANDIDATE') || $this->isGranted('ROLE_ADMIN'));

        return $this->render('position/show.html.twig', [
            'position' => $position,
            'cvs' => $list,
            'likes' => $likeCounts,
            'names' => $ownerNames,
            'averages' => $averages,
            'posts' => $discussion,
            'authorNames' => $authorNames,
            'ownCv' => $ownCv,
            'canCreate' => $canCreate,
            'discussionForm' => $this->createForm(DiscussionFormType::class),
        ]);
    }

    #[Route('/positions/{id}/edit', name: 'app_position_edit', requirements: ['id' => '\d+'])]
    public function edit(Position $position, Request $request, EntityManagerInterface $em, PositionEditor $editor, CvAttributeRepository $attributes, AttributeCategoryRepository $categories, PositionRepository $positions): Response
    {
        $position = $positions->findOneDetailed((int) $position->getId()) ?? $position;
        $this->denyAccessUnlessGranted(PositionVoter::EDIT, $position);
        if ($request->isMethod('POST') && (int) ($request->request->all('position_form')['version'] ?? -1) !== $position->getVersion()) {
            $this->addFlash('danger', 'error.version_conflict');

            return $this->redirectToRoute('app_position_edit', ['id' => $position->getId()]);
        }
        $form = $this->createForm(PositionFormType::class, $position, ['version' => $position->getVersion()]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $editor->sync($position, $request->request->all('attribute_ids'), $request->request->all('tags'), $request->request->all('rules'));
            try {
                $em->flush();
            } catch (OptimisticLockException) {
                $this->addFlash('danger', 'error.version_conflict');

                return $this->redirectToRoute('app_position_edit', ['id' => $position->getId()]);
            }
            $this->addFlash('success', 'flash.saved');

            return $this->redirectToRoute('app_position_show', ['id' => $position->getId()]);
        }

        return $this->render('position/form.html.twig', [
            'form' => $form,
            'position' => $position,
            'categories' => $categories->findAllOrdered(),
            'catalog' => $this->catalog($attributes),
        ]);
    }

    #[Route('/positions/{id}/duplicate', name: 'app_position_duplicate', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function duplicate(Position $position, Request $request, PositionEditor $editor, EntityManagerInterface $em, TranslatorInterface $translator): Response
    {
        $this->denyAccessUnlessGranted(PositionVoter::EDIT, $position);
        if (!$this->isCsrfTokenValid('submit', $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException();
        }
        $copy = $editor->duplicate($position, $translator->trans('position.copy_of', ['%title%' => $position->getTitle()]));
        $em->flush();
        $this->addFlash('success', 'flash.duplicated');

        return $this->redirectToRoute('app_position_edit', ['id' => $copy->getId()]);
    }

    #[Route('/positions/{id}/delete', name: 'app_position_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Position $position, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted(PositionVoter::EDIT, $position);
        if (!$this->isCsrfTokenValid('submit', $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException();
        }
        if ((int) $request->request->get('version') !== $position->getVersion()) {
            $this->addFlash('danger', 'error.version_conflict');

            return $this->redirectToRoute('app_position_index');
        }
        $em->remove($position);
        try {
            $em->flush();
            $this->addFlash('success', 'flash.deleted');
        } catch (OptimisticLockException) {
            $this->addFlash('danger', 'error.version_conflict');
        }

        return $this->redirectToRoute('app_position_index');
    }

    #[Route('/positions/{id}/discussion', name: 'app_position_discussion', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function discussion(Position $position, Request $request, DiscussionPostRepository $posts, NameResolver $names, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted(PositionVoter::VIEW, $position);
        $after = $request->query->getInt('after');
        if ($request->isMethod('POST')) {
            $user = $this->getUser();
            if (!$user instanceof User) {
                throw $this->createAccessDeniedException();
            }
            $form = $this->createForm(DiscussionFormType::class);
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $post = new \App\Entity\DiscussionPost($position, $user, (string) $form->get('body')->getData());
                $em->persist($post);
                $em->flush();
                if ($request->isXmlHttpRequest()) {
                    return $this->render('position/_posts.html.twig', [
                        'posts' => [$post],
                        'authorNames' => $names->forUsers([$user]),
                    ]);
                }

                return $this->redirectToRoute('app_position_show', ['id' => $position->getId()]);
            }
        }
        $items = $posts->forPosition($position, $after > 0 ? $after : null);

        return $this->render('position/_posts.html.twig', [
            'posts' => $items,
            'authorNames' => $names->forUsers(array_map(static fn ($post) => $post->getAuthor(), $items)),
        ]);
    }

    private function catalog(CvAttributeRepository $attributes): array
    {
        $catalog = [];
        foreach ($attributes->findLibrary() as $attribute) {
            $options = [];
            foreach ($attribute->getOptions() as $option) {
                $options[] = ['id' => $option->getId(), 'label' => $option->getLabel()];
            }
            $catalog[] = [
                'id' => $attribute->getId(),
                'name' => $attribute->getName(),
                'type' => $attribute->getType()->value,
                'category' => $attribute->getCategory()?->getCode(),
                'options' => $options,
            ];
        }

        return $catalog;
    }
}
