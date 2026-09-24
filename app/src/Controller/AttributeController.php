<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\AttributeType;
use App\Entity\CvAttribute;
use App\Form\AttributeFormType;
use App\Repository\AttributeCategoryRepository;
use App\Repository\CvAttributeRepository;
use App\Service\OptionSynchronizer;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AttributeController extends AbstractController
{
    #[Route('/library', name: 'app_attribute_index')]
    public function index(CvAttributeRepository $attributes): Response
    {
        $this->denyAccessUnlessGranted('ROLE_RECRUITER');

        return $this->render('attribute/index.html.twig', [
            'attributes' => $attributes->findLibrary(),
        ]);
    }

    #[Route('/attributes/lookup', name: 'app_attribute_lookup')]
    public function lookup(Request $request, CvAttributeRepository $attributes): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $prefix = trim($request->query->getString('q'));
        $category = $request->query->getString('category') ?: null;
        $items = $prefix === '' ? $attributes->recent(12) : $attributes->lookup($prefix, $category, 20);
        if ($prefix !== '' && $category) {
            $items = $attributes->lookup($prefix, $category, 20);
        }

        return $this->json(['items' => array_map(static function (array $row): array {
            $type = $row['type'];

            return [
                'id' => (int) $row['id'],
                'name' => $row['name'],
                'category' => $row['category'],
                'type' => $type instanceof AttributeType ? $type->value : (string) $type,
            ];
        }, $items)]);
    }

    #[Route('/library/new', name: 'app_attribute_new')]
    public function new(Request $request, EntityManagerInterface $em, OptionSynchronizer $options): Response
    {
        $this->denyAccessUnlessGranted('ROLE_RECRUITER');
        $attribute = new CvAttribute();
        $form = $this->createForm(AttributeFormType::class, $attribute);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($attribute->getType() === AttributeType::OneOfMany && trim((string) $form->get('optionsText')->getData()) === '') {
                $this->addFlash('danger', 'error.invalid');

                return $this->render('attribute/form.html.twig', ['form' => $form, 'attribute' => $attribute]);
            }
            $options->sync($attribute, (string) $form->get('optionsText')->getData());
            $attribute->touch();
            $em->persist($attribute);
            $em->flush();
            $this->addFlash('success', 'flash.created');

            return $this->redirectToRoute('app_attribute_index');
        }

        return $this->render('attribute/form.html.twig', ['form' => $form, 'attribute' => $attribute]);
    }

    #[Route('/library/{id}/edit', name: 'app_attribute_edit', requirements: ['id' => '\d+'])]
    public function edit(CvAttribute $attribute, Request $request, EntityManagerInterface $em, OptionSynchronizer $options): Response
    {
        $this->denyAccessUnlessGranted('ROLE_RECRUITER');
        if ($request->isMethod('POST') && (int) $request->request->all('attribute_form')['version'] !== $attribute->getVersion()) {
            $this->addFlash('danger', 'error.version_conflict');

            return $this->redirectToRoute('app_attribute_edit', ['id' => $attribute->getId()]);
        }
        $text = implode("\n", array_map(static fn ($option) => $option->getLabel(), $attribute->getOptions()->toArray()));
        $form = $this->createForm(AttributeFormType::class, $attribute, [
            'builtin' => $attribute->isBuiltin(),
            'options_text' => $text,
            'version' => $attribute->getVersion(),
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $options->sync($attribute, (string) $form->get('optionsText')->getData());
            $attribute->touch();
            try {
                $em->flush();
            } catch (OptimisticLockException) {
                $this->addFlash('danger', 'error.version_conflict');

                return $this->redirectToRoute('app_attribute_edit', ['id' => $attribute->getId()]);
            }
            $this->addFlash('success', 'flash.saved');

            return $this->redirectToRoute('app_attribute_index');
        }

        return $this->render('attribute/form.html.twig', ['form' => $form, 'attribute' => $attribute]);
    }

    #[Route('/library/{id}/delete', name: 'app_attribute_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(CvAttribute $attribute, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_RECRUITER');
        if (!$this->isCsrfTokenValid('submit', $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException();
        }
        if ($attribute->isBuiltin()) {
            $this->addFlash('danger', 'error.builtin');

            return $this->redirectToRoute('app_attribute_index');
        }
        if ((int) $request->request->get('version') !== $attribute->getVersion()) {
            $this->addFlash('danger', 'error.version_conflict');

            return $this->redirectToRoute('app_attribute_index');
        }
        $em->remove($attribute);
        try {
            $em->flush();
            $this->addFlash('success', 'flash.deleted');
        } catch (OptimisticLockException) {
            $this->addFlash('danger', 'error.version_conflict');
        }

        return $this->redirectToRoute('app_attribute_index');
    }
}
