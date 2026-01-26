<?php

namespace Nadi\Shipper;

use Nadi\Shipper\Exceptions\UnsupportedPlatformException;

class PlatformDetector
{
    /**
     * Supported OS/architecture combinations.
     */
    private const SUPPORTED_PLATFORMS = [
        'darwin' => ['amd64', 'arm64'],
        'linux' => ['amd64', '386', 'arm64'],
        'windows' => ['amd64'],
    ];

    /**
     * Get the normalized operating system name.
     */
    public function getOS(): string
    {
        $os = strtolower(php_uname('s'));

        if (str_contains($os, 'darwin')) {
            return 'darwin';
        }

        if (str_contains($os, 'linux')) {
            return 'linux';
        }

        if (str_contains($os, 'windows') || str_contains($os, 'win')) {
            return 'windows';
        }

        return $os;
    }

    /**
     * Get the normalized architecture name.
     */
    public function getArch(): string
    {
        $arch = strtolower(php_uname('m'));

        return match ($arch) {
            'x86_64', 'amd64' => 'amd64',
            'i386', 'i486', 'i586', 'i686', 'i786', 'i886', 'i986' => '386',
            'aarch64', 'arm64' => 'arm64',
            default => $arch,
        };
    }

    /**
     * Get the binary download filename for a given version.
     */
    public function getBinaryName(string $version): string
    {
        $os = $this->getOS();
        $arch = $this->getArch();
        $extension = $os === 'windows' ? '.zip' : '.tar.gz';

        return "shipper-{$version}-{$os}-{$arch}{$extension}";
    }

    /**
     * Get the executable filename for the current platform.
     */
    public function getExecutableName(): string
    {
        return $this->getOS() === 'windows' ? 'shipper.exe' : 'shipper';
    }

    /**
     * Check if the current platform is supported.
     */
    public function isSupported(): bool
    {
        $os = $this->getOS();
        $arch = $this->getArch();

        if (! isset(self::SUPPORTED_PLATFORMS[$os])) {
            return false;
        }

        return in_array($arch, self::SUPPORTED_PLATFORMS[$os], true);
    }

    /**
     * Validate that the current platform is supported.
     *
     * @throws UnsupportedPlatformException
     */
    public function validate(): void
    {
        if (! $this->isSupported()) {
            $os = $this->getOS();
            $arch = $this->getArch();

            throw new UnsupportedPlatformException(
                "Unsupported platform: {$os}/{$arch}. ".
                'Supported platforms: '.
                implode(', ', $this->getSupportedPlatformsDescription())
            );
        }
    }

    /**
     * Get a human-readable list of supported platforms.
     *
     * @return array<string>
     */
    public function getSupportedPlatformsDescription(): array
    {
        $descriptions = [];

        foreach (self::SUPPORTED_PLATFORMS as $os => $architectures) {
            foreach ($architectures as $arch) {
                $descriptions[] = "{$os}/{$arch}";
            }
        }

        return $descriptions;
    }
}
