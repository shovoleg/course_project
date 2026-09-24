<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\ImageHostNotConfigured;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class ImgbbUploader
{
    public function __construct(
        private HttpClientInterface $http,
        #[Autowire('%env(default::IMGBB_API_KEY)%')]
        private string $apiKey = '',
        #[Autowire('%kernel.project_dir%')]
        private string $projectDir = '',
    ) {
        $this->apiKey = $this->clean($this->apiKey);
        if ($this->apiKey === '') {
            $this->apiKey = $this->readProjectEnv()['IMGBB_API_KEY'] ?? '';
        }
    }

    public function isConfigured(): bool
    {
        return $this->apiKey !== '';
    }

    public function configurationError(): string
    {
        $root = $this->projectDir !== '' ? $this->projectDir : dirname(__DIR__, 2);
        if (!is_file($root.'/.env') && !is_file($root.'/.env.local')) {
            return 'Файл app/.env не найден.';
        }

        return 'В app/.env не заполнен IMGBB_API_KEY.';
    }

    public function upload(UploadedFile $file): array
    {
        if (!$this->isConfigured()) {
            throw new ImageHostNotConfigured($this->configurationError());
        }
        $form = new FormDataPart([
            'image' => DataPart::fromPath($file->getPathname(), $file->getClientOriginalName()),
        ]);
        $response = $this->http->request('POST', 'https://api.imgbb.com/1/upload?key='.rawurlencode($this->apiKey), [
            'headers' => $form->getPreparedHeaders()->toArray(),
            'body' => $form->bodyToIterable(),
        ]);
        try {
            $data = $response->toArray();
        } catch (HttpExceptionInterface $exception) {
            $payload = $exception->getResponse()->toArray(false);
            $message = is_array($payload['error'] ?? null) ? (string) ($payload['error']['message'] ?? '') : '';
            throw new \RuntimeException($message !== '' ? $message : 'Image upload failed.');
        }
        $url = (string) ($data['data']['url'] ?? $data['data']['image']['url'] ?? '');
        if ($url === '') {
            $message = is_array($data['error'] ?? null) ? (string) ($data['error']['message'] ?? '') : '';
            throw new \RuntimeException($message !== '' ? $message : 'Image upload failed.');
        }

        return [
            'url' => $url,
            'publicId' => (string) ($data['data']['id'] ?? ''),
        ];
    }

    private function readProjectEnv(): array
    {
        $vars = [];
        $root = $this->projectDir !== '' ? $this->projectDir : dirname(__DIR__, 2);
        foreach (['.env', '.env.local'] as $name) {
            $path = $root.'/'.$name;
            if (!is_file($path)) {
                continue;
            }
            $lines = file($path, FILE_IGNORE_NEW_LINES);
            if ($lines === false) {
                continue;
            }
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#')) {
                    continue;
                }
                if (str_starts_with($line, 'export ')) {
                    $line = trim(substr($line, 7));
                }
                if (!str_contains($line, '=')) {
                    continue;
                }
                [$key, $value] = explode('=', $line, 2);
                $key = $this->clean($key);
                $value = $this->clean($value);
                if ($key === 'IMGBB_API_KEY' && $value !== '') {
                    $vars[$key] = $value;
                }
            }
        }

        return $vars;
    }

    private function clean(string $value): string
    {
        $value = preg_replace('/^\xEF\xBB\xBF/', '', $value) ?? $value;
        $value = trim($value);
        if (strlen($value) >= 2) {
            $quote = $value[0];
            if (($quote === '"' || $quote === "'") && str_ends_with($value, $quote)) {
                $value = substr($value, 1, -1);
            }
        }

        return trim($value);
    }
}
