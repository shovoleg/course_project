<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

final class SalesforceClient
{
    public function __construct(
        private HttpClientInterface $http,
        private string $instanceUrl = '',
        private string $clientId = '',
        private string $clientSecret = '',
        private string $username = '',
        private string $password = '',
        private string $securityToken = '',
    ) {
        $this->instanceUrl = $_ENV['SF_INSTANCE_URL'] ?? $_ENV['SALESFORCE_INSTANCE_URL'] ?? $instanceUrl;
        $this->clientId = $_ENV['SF_CLIENT_ID'] ?? $_ENV['SALESFORCE_CLIENT_ID'] ?? $clientId;
        $this->clientSecret = $_ENV['SF_CLIENT_SECRET'] ?? $_ENV['SALESFORCE_CLIENT_SECRET'] ?? $clientSecret;
        $this->username = $_ENV['SF_USERNAME'] ?? $_ENV['SALESFORCE_USERNAME'] ?? $username;
        $this->password = $_ENV['SF_PASSWORD'] ?? $_ENV['SALESFORCE_PASSWORD'] ?? $password;
        $this->securityToken = $_ENV['SF_SECURITY_TOKEN'] ?? $_ENV['SALESFORCE_SECURITY_TOKEN'] ?? $securityToken;
    }

    public function isConfigured(): bool
    {
        return $this->instanceUrl !== '' && $this->clientId !== '' && $this->username !== '' && $this->password !== '';
    }

    private function authenticate(): array
    {
        if (!$this->isConfigured()) {
            throw new \RuntimeException('Salesforce not configured');
        }
        $candidates = [
            'https://login.salesforce.com/services/oauth2/token',
            rtrim($this->instanceUrl, '/') . '/services/oauth2/token',
        ];
        $candidates = array_unique($candidates);
        $lastError = null;
        foreach ($candidates as $url) {
            $response = $this->http->request('POST', $url, [
                'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
                'body' => http_build_query([
                    'grant_type' => 'password',
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'username' => $this->username,
                    'password' => $this->password . $this->securityToken,
                ]),
            ]);
            $data = $response->toArray(false);
            if (isset($data['access_token'])) {
                return $data;
            }
            $lastError = $data['error_description'] ?? $data['error'] ?? json_encode($data);
            if (str_contains(strtolower($lastError), 'client identifier invalid') && $url === $candidates[0]) {
                continue;
            }
            break;
        }
        throw new \RuntimeException($lastError ?? 'Salesforce auth failed');
    }

    public function createAccountWithContact(array $accountData, array $contactData): array
    {
        if (!$this->isConfigured()) {
            return ['accountId' => 'mock_' . bin2hex(random_bytes(4)), 'contactId' => 'mock_' . bin2hex(random_bytes(4)), 'mock' => true];
        }
        $auth = $this->authenticate();
        $token = $auth['access_token'];
        $instance = $auth['instance_url'] ?? $this->instanceUrl;
        $acc = $this->http->request('POST', rtrim($instance, '/') . '/services/data/v59.0/sobjects/Account', [
            'headers' => ['Authorization' => 'Bearer ' . $token, 'Content-Type' => 'application/json'],
            'json' => $accountData,
        ])->toArray(false);
        if (!isset($acc['id']) || !($acc['success'] ?? false)) {
            throw new \RuntimeException(json_encode($acc));
        }
        $accountId = $acc['id'];
        $contactData['AccountId'] = $accountId;
        $con = $this->http->request('POST', rtrim($instance, '/') . '/services/data/v59.0/sobjects/Contact', [
            'headers' => ['Authorization' => 'Bearer ' . $token, 'Content-Type' => 'application/json'],
            'json' => $contactData,
        ])->toArray(false);
        if (!isset($con['id']) || !($con['success'] ?? false)) {
            throw new \RuntimeException(json_encode($con));
        }
        return ['accountId' => $accountId, 'contactId' => $con['id'], 'mock' => false, 'instance' => $instance];
    }
}
