<?php

declare(strict_types=1);

namespace App\Controller;

use App\Exception\ImageHostNotConfigured;
use App\Service\ImgbbUploader;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

final class UploadController extends AbstractController
{
    #[Route('/upload/image', name: 'app_upload_image', methods: ['POST'])]
    public function image(Request $request, ImgbbUploader $images, TranslatorInterface $translator): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        if (!$this->isCsrfTokenValid('submit', (string) $request->headers->get('X-CSRF-TOKEN'))) {
            return $this->json(['error' => $translator->trans('error.invalid')], 403);
        }
        $file = $request->files->get('file');
        if (!$file || !$file->isValid() || !str_starts_with((string) $file->getMimeType(), 'image/') || $file->getSize() > 5_000_000) {
            return $this->json(['error' => $translator->trans('upload.failed')], 400);
        }
        try {
            return $this->json($images->upload($file));
        } catch (ImageHostNotConfigured $exception) {
            $detail = trim($exception->getMessage());
            $error = $translator->trans('error.cloud_missing');
            if ($detail !== '') {
                $error .= ' '.$detail;
            }

            return $this->json(['error' => $error], 503);
        } catch (\RuntimeException $exception) {
            $message = trim($exception->getMessage());
            if ($message === 'Invalid API v1 key.') {
                $message = $translator->trans('error.imgbb_key');
            }

            return $this->json(['error' => $message !== '' && $message !== 'Image upload failed.' ? $message : $translator->trans('upload.failed')], 502);
        } catch (\Throwable) {
            return $this->json(['error' => $translator->trans('upload.failed')], 502);
        }
    }
}
