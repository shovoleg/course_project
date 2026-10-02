<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Form\SalesforceFormType;
use App\Service\SalesforceClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SalesforceController extends AbstractController
{
    #[Route('/profile/{id}/salesforce', name: 'app_salesforce_form', methods: ['GET', 'POST'])]
    public function form(User $profile, Request $request, SalesforceClient $sf): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User || ($user->getId() !== $profile->getId() && !$this->isGranted('ROLE_ADMIN'))) {
            throw $this->createAccessDeniedException();
        }
        $form = $this->createForm(SalesforceFormType::class, [
            'company' => explode('@', $profile->getEmail())[0] . ' Corp',
            'firstName' => explode('@', $profile->getEmail())[0],
            'lastName' => 'Candidate',
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $d = $form->getData();
            $accountData = [
                'Name' => $d['company'],
                'Phone' => $d['phone'] ?? null,
                'Industry' => $d['industry'] ?? null,
                'Description' => sprintf("User ID %d | Email %s | Locale %s | Theme %s | Newsletter %s | %s", $profile->getId(), $profile->getEmail(), $profile->getLocale(), $profile->getTheme(), $d['newsletter'] ? 'yes' : 'no', $d['description'] ?? ''),
                'Type' => 'Customer',
            ];
            $contactData = [
                'FirstName' => $d['firstName'],
                'LastName' => $d['lastName'],
                'Email' => $profile->getEmail(),
                'Phone' => $d['phone'] ?? null,
                'Description' => $d['description'] ?? null,
                'LeadSource' => 'Web',
            ];
            try {
                $res = $sf->createAccountWithContact($accountData, $contactData);
                $this->addFlash('success', $res['mock'] ?? false ? 'salesforce.mock' : 'salesforce.success');
                if (!($res['mock'] ?? false)) {
                    $this->addFlash('success', sprintf('Salesforce ID %s / %s', $res['accountId'], $res['contactId']));
                }
                return $this->redirectToRoute('app_profile_show', ['id' => $profile->getId()]);
            } catch (\Throwable $e) {
                $this->addFlash('danger', 'salesforce.error: ' . $e->getMessage());
            }
        }
        return $this->render('salesforce/form.html.twig', ['profile' => $profile, 'form' => $form, 'configured' => $sf->isConfigured()]);
    }
}
