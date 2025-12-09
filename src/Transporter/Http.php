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

    protected $configurations = [];

    protected $storage = [];

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
     * Build authentication headers using App ID + App Secret.
     */
    protected function buildAuthHeaders(string $version): array
    {
        $appId = $this->configurations['app_id'] ?? null;
        $appSecret = $this->configurations['app_secret'] ?? null;

        TransporterException::throwIfMissingAppCredentials($appId, $appSecret);

        return [
            'Accept' => 'application/vnd.nadi.'.$version.'+json',
            'Nadi-Transporter-Id' => $this->getTransporterId(),
            'Content-Type' => 'application/json',
            'Nadi-App-Id' => $appId,
            'Nadi-App-Secret' => $appSecret,
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
