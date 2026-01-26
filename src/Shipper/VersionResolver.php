<?php

namespace Nadi\Shipper;

use Nadi\Shipper\Exceptions\DownloadException;

class VersionResolver
{
    private const REPOSITORY = 'nadi-pro/shipper';

    private const GITHUB_API_URL = 'https://api.github.com/repos/%s/releases/latest';

    private const DOWNLOAD_URL = 'https://github.com/%s/releases/download/%s/%s';

    private const REFERENCE_CONFIG_URL = 'https://raw.githubusercontent.com/%s/master/nadi.reference.yaml';

    private string $repository;

    public function __construct(?string $repository = null)
    {
        $this->repository = $repository ?? self::REPOSITORY;
    }

    /**
     * Get the latest release version from GitHub.
     *
     * @throws DownloadException
     */
    public function getLatestVersion(): string
    {
        $url = sprintf(self::GITHUB_API_URL, $this->repository);

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => [
                    'User-Agent: Nadi-PHP-SDK',
                    'Accept: application/vnd.github.v3+json',
                ],
                'timeout' => 30,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            throw new DownloadException(
                "Failed to fetch latest version from GitHub API: {$url}. ".
                'Please check your network connection and try again.'
            );
        }

        $data = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new DownloadException(
                'Failed to parse GitHub API response: '.json_last_error_msg()
            );
        }

        if (! isset($data['tag_name'])) {
            throw new DownloadException(
                'GitHub API response does not contain a tag_name. '.
                'The repository may not have any releases yet.'
            );
        }

        return $data['tag_name'];
    }

    /**
     * Get the download URL for a specific version and binary name.
     */
    public function getReleaseUrl(string $version, string $binaryName): string
    {
        return sprintf(self::DOWNLOAD_URL, $this->repository, $version, $binaryName);
    }

    /**
     * Get the reference configuration URL.
     */
    public function getReferenceConfigUrl(): string
    {
        return sprintf(self::REFERENCE_CONFIG_URL, $this->repository);
    }

    /**
     * Download the reference configuration file.
     *
     * @throws DownloadException
     */
    public function downloadReferenceConfig(): string
    {
        $url = $this->getReferenceConfigUrl();

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => [
                    'User-Agent: Nadi-PHP-SDK',
                ],
                'timeout' => 30,
            ],
        ]);

        $content = @file_get_contents($url, false, $context);

        if ($content === false) {
            throw new DownloadException(
                "Failed to download reference configuration from: {$url}"
            );
        }

        return $content;
    }
}
