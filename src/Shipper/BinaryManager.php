<?php

namespace Nadi\Shipper;

use Nadi\Shipper\Exceptions\DownloadException;
use Nadi\Shipper\Exceptions\ExtractionException;
use Nadi\Shipper\Exceptions\ShipperException;
use PharData;

class BinaryManager
{
    private const VERSION_FILE = '.shipper-version';

    private string $binaryDirectory;

    private PlatformDetector $platformDetector;

    private VersionResolver $versionResolver;

    public function __construct(
        string $binaryDirectory,
        ?PlatformDetector $platformDetector = null,
        ?VersionResolver $versionResolver = null
    ) {
        $this->binaryDirectory = rtrim($binaryDirectory, '/\\');
        $this->platformDetector = $platformDetector ?? new PlatformDetector;
        $this->versionResolver = $versionResolver ?? new VersionResolver;
    }

    /**
     * Install the shipper binary.
     *
     * @param  string|null  $version  Specific version to install, or null for latest
     * @return string The path to the installed binary
     *
     * @throws ShipperException
     */
    public function install(?string $version = null): string
    {
        $this->platformDetector->validate();

        $version = $version ?? $this->versionResolver->getLatestVersion();

        $this->ensureDirectoryExists();

        $downloadPath = $this->downloadBinary($version);

        try {
            $this->extractBinary($downloadPath);
        } finally {
            if (file_exists($downloadPath)) {
                @unlink($downloadPath);
            }
        }

        $this->setPermissions();
        $this->saveVersion($version);

        return $this->getBinaryPath();
    }

    /**
     * Check if the shipper binary is installed.
     */
    public function isInstalled(): bool
    {
        $binaryPath = $this->getBinaryPath();

        return file_exists($binaryPath) && is_executable($binaryPath);
    }

    /**
     * Get the full path to the shipper binary.
     */
    public function getBinaryPath(): string
    {
        return $this->binaryDirectory.'/'.$this->platformDetector->getExecutableName();
    }

    /**
     * Get the binary directory path.
     */
    public function getBinaryDirectory(): string
    {
        return $this->binaryDirectory;
    }

    /**
     * Get the currently installed version.
     */
    public function getInstalledVersion(): ?string
    {
        $versionFile = $this->binaryDirectory.'/'.self::VERSION_FILE;

        if (! file_exists($versionFile)) {
            return null;
        }

        $version = trim(file_get_contents($versionFile));

        return $version !== '' ? $version : null;
    }

    /**
     * Check if the installed binary needs an update.
     *
     * @throws DownloadException
     */
    public function needsUpdate(): bool
    {
        if (! $this->isInstalled()) {
            return true;
        }

        $installedVersion = $this->getInstalledVersion();

        if ($installedVersion === null) {
            return true;
        }

        $latestVersion = $this->versionResolver->getLatestVersion();

        return version_compare(
            ltrim($installedVersion, 'v'),
            ltrim($latestVersion, 'v'),
            '<'
        );
    }

    /**
     * Update the binary to the latest version if needed.
     *
     * @return string|null The new version if updated, null if already up to date
     *
     * @throws ShipperException
     */
    public function update(): ?string
    {
        if (! $this->needsUpdate()) {
            return null;
        }

        $latestVersion = $this->versionResolver->getLatestVersion();
        $this->install($latestVersion);

        return $latestVersion;
    }

    /**
     * Uninstall the shipper binary and version file.
     */
    public function uninstall(): void
    {
        $binaryPath = $this->getBinaryPath();
        $versionFile = $this->binaryDirectory.'/'.self::VERSION_FILE;

        if (file_exists($binaryPath)) {
            @unlink($binaryPath);
        }

        if (file_exists($versionFile)) {
            @unlink($versionFile);
        }
    }

    /**
     * Execute the shipper binary with arguments.
     *
     * @param  array<string>  $arguments
     * @return array{output: string, exitCode: int}
     *
     * @throws ShipperException
     */
    public function execute(array $arguments = []): array
    {
        if (! $this->isInstalled()) {
            throw new ShipperException(
                'Shipper binary is not installed. Call install() first.'
            );
        }

        $command = escapeshellcmd($this->getBinaryPath());

        foreach ($arguments as $arg) {
            $command .= ' '.escapeshellarg($arg);
        }

        $output = [];
        $exitCode = 0;

        exec($command.' 2>&1', $output, $exitCode);

        return [
            'output' => implode("\n", $output),
            'exitCode' => $exitCode,
        ];
    }

    /**
     * Ensure the binary directory exists.
     *
     * @throws ShipperException
     */
    private function ensureDirectoryExists(): void
    {
        if (! is_dir($this->binaryDirectory)) {
            if (! @mkdir($this->binaryDirectory, 0755, true)) {
                throw new ShipperException(
                    "Failed to create binary directory: {$this->binaryDirectory}"
                );
            }
        }

        if (! is_writable($this->binaryDirectory)) {
            throw new ShipperException(
                "Binary directory is not writable: {$this->binaryDirectory}"
            );
        }
    }

    /**
     * Download the binary for the specified version.
     *
     * @throws DownloadException
     */
    private function downloadBinary(string $version): string
    {
        $binaryName = $this->platformDetector->getBinaryName($version);
        $url = $this->versionResolver->getReleaseUrl($version, $binaryName);

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => [
                    'User-Agent: Nadi-PHP-SDK',
                ],
                'timeout' => 300,
                'follow_location' => true,
            ],
        ]);

        $content = @file_get_contents($url, false, $context);

        if ($content === false) {
            throw new DownloadException(
                "Failed to download shipper binary from: {$url}. ".
                'Please check your network connection and try again.'
            );
        }

        $tmpDir = sys_get_temp_dir();
        $tmpFile = $tmpDir.'/'.$binaryName;

        if (file_put_contents($tmpFile, $content) === false) {
            throw new DownloadException(
                "Failed to write downloaded binary to: {$tmpFile}"
            );
        }

        return $tmpFile;
    }

    /**
     * Extract the binary from the downloaded archive.
     *
     * @throws ExtractionException
     */
    private function extractBinary(string $archivePath): void
    {
        $os = $this->platformDetector->getOS();

        if ($os === 'windows') {
            $this->extractZip($archivePath);
        } else {
            $this->extractTarGz($archivePath);
        }
    }

    /**
     * Extract a .tar.gz archive.
     *
     * @throws ExtractionException
     */
    private function extractTarGz(string $archivePath): void
    {
        if (! class_exists('PharData')) {
            throw new ExtractionException(
                'PharData extension is required to extract tar.gz archives.'
            );
        }

        try {
            $phar = new PharData($archivePath);
            $phar->extractTo($this->binaryDirectory, null, true);
        } catch (\Exception $e) {
            throw new ExtractionException(
                "Failed to extract archive: {$e->getMessage()}"
            );
        }
    }

    /**
     * Extract a .zip archive.
     *
     * @throws ExtractionException
     */
    private function extractZip(string $archivePath): void
    {
        if (! class_exists('ZipArchive')) {
            throw new ExtractionException(
                'ZipArchive extension is required to extract zip archives on Windows.'
            );
        }

        $zip = new \ZipArchive;

        if ($zip->open($archivePath) !== true) {
            throw new ExtractionException(
                "Failed to open zip archive: {$archivePath}"
            );
        }

        try {
            $zip->extractTo($this->binaryDirectory);
        } finally {
            $zip->close();
        }
    }

    /**
     * Set executable permissions on the binary.
     *
     * @throws ShipperException
     */
    private function setPermissions(): void
    {
        $binaryPath = $this->getBinaryPath();

        if (! file_exists($binaryPath)) {
            throw new ShipperException(
                "Binary not found after extraction: {$binaryPath}"
            );
        }

        if ($this->platformDetector->getOS() !== 'windows') {
            if (! @chmod($binaryPath, 0755)) {
                throw new ShipperException(
                    "Failed to set executable permissions on: {$binaryPath}"
                );
            }
        }
    }

    /**
     * Save the installed version to a file.
     */
    private function saveVersion(string $version): void
    {
        $versionFile = $this->binaryDirectory.'/'.self::VERSION_FILE;
        @file_put_contents($versionFile, $version);
    }
}
