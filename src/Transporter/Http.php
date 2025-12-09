<?php

namespace Nadi\Transporter;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Nadi\Concerns\InteractsWithTransporterId;
use Nadi\Exceptions\TransporterException;

class Http implements Contract
{
    use InteractsWithTransporterId;

    const VERSION = 'v1';

    const ENDPOINT = 'https://nadi.pro/api';

    /**
     * Authentication schemes supported by the transporter.
     */
    const AUTH_LEGACY = 'legacy';       // Authorization Bearer + Nadi-App-Token
    const AUTH_APP_SECRET = 'app_secret'; // Nadi-App-Id + Nadi-App-Secret

    protected Client $client;

    protected string $endpoint;

    protected $configurations = [];

    protected $storage = [];

    protected string $authScheme = self::AUTH_LEGACY;

    public function configure(array $configurations = []): self
    {
        $this->configurations = $configurations;

        $version = $this->configurations['version'] ?? self::VERSION;
        $endpoint = $this->configurations['endpoint'] ?? self::ENDPOINT;

        $this->endpoint = $endpoint;

        // Determine authentication scheme based on provided credentials
        $headers = $this->buildAuthHeaders($version);

        $this->setClient(
            new Client([
                'headers' => $headers,
            ])
        );

        return $this;
    }

    /**
     * Build authentication headers based on available credentials.
     * Prefers new App ID + Secret scheme if both are provided.
     */
    protected function buildAuthHeaders(string $version): array
    {
        $headers = [
            'Accept' => 'application/vnd.nadi.'.$version.'+json',
            'Nadi-Transporter-Id' => $this->getTransporterId(),
            'Content-Type' => 'application/json',
        ];

        // Check for new auth scheme (App ID + App Secret)
        $appId = $this->configurations['app_id'] ?? null;
        $appSecret = $this->configurations['app_secret'] ?? null;

        if ($appId && $appSecret) {
            $this->authScheme = self::AUTH_APP_SECRET;
            $headers['Nadi-App-Id'] = $appId;
            $headers['Nadi-App-Secret'] = $appSecret;

            return $headers;
        }

        // Fall back to legacy auth (Bearer + App Token)
        $key = $this->configurations['key'] ?? null;
        $token = $this->configurations['token'] ?? null;

        TransporterException::throwIfMissingCredentials($key, $token);

        $this->authScheme = self::AUTH_LEGACY;
        $headers['Authorization'] = 'Bearer '.$key;
        $headers['Nadi-App-Token'] = $token;

        return $headers;
    }

    /**
     * Get the current authentication scheme being used.
     */
    public function getAuthScheme(): string
    {
        return $this->authScheme;
    }

    public function setClient(Client $client): self
    {
        $this->client = $client;

        return $this;
    }

    public function getClient()
    {
        return $this->client;
    }

    public function store(array $data): self
    {
        $this->storage[] = $data;

        return $this;
    }

    public function send()
    {
        if (empty($this->storage)) {
            return true;
        }

        return $this->client->post($this->url('record'), [RequestOptions::JSON => $this->storage]);
    }

    public function test()
    {
        $response = $this->client->post($this->url('test'));

        return $response->getStatusCode() == 200;
    }

    public function verify()
    {
        $response = $this->client->post($this->url('verify'));

        return $response->getStatusCode() == 200;
    }

    public function url(string $endpoint)
    {
        return rtrim($this->endpoint, '/').'/'.trim($endpoint, '/');
    }
}
