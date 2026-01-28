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

    protected Client $client;

    protected string $endpoint;

    protected string $version;

    protected $configurations = [];

    protected $storage = [];

    public function configure(array $configurations = []): self
    {
        $this->configurations = $configurations;

        $this->version = $this->configurations['version'] ?? self::VERSION;
        $endpoint = $this->configurations['endpoint'] ?? self::ENDPOINT;

        $this->endpoint = $endpoint;

        // Determine authentication scheme based on provided credentials
        $headers = $this->buildAuthHeaders();

        $this->setClient(
            new Client([
                'headers' => $headers,
            ])
        );

        return $this;
    }

    /**
     * Build authentication headers.
     *
     * Authentication scheme (consistent with shipper):
     * - Authorization: Bearer {apiKey} - Sanctum authentication (NADI_API_KEY)
     * - Nadi-App-Token: {appKey} - Application identifier (NADI_APP_KEY)
     * - Nadi-API-Version: v1 - API version
     */
    protected function buildAuthHeaders(): array
    {
        $apiKey = $this->configurations['apiKey'] ?? $this->configurations['api_key'] ?? null;
        $appKey = $this->configurations['appKey'] ?? $this->configurations['app_key'] ?? $this->configurations['token'] ?? null;

        TransporterException::throwIfMissingAppCredentials($apiKey, $appKey);

        return [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer '.$apiKey,
            'Nadi-App-Token' => $appKey,
            'Nadi-API-Version' => $this->version,
            'Nadi-Transporter-Id' => $this->getTransporterId(),
        ];
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
