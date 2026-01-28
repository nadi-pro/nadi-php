# Shipper Binary Manager

The SDK includes a shared library for managing the Nadi Shipper binary, enabling automatic
download and installation across platforms.

## Basic Usage

```php
use Nadi\Shipper\BinaryManager;

// Create manager with target directory
$manager = new BinaryManager('/path/to/bin');

// Install latest version
$binaryPath = $manager->install();

// Check installation status
if ($manager->isInstalled()) {
    echo "Path: " . $manager->getBinaryPath();
    echo "Version: " . $manager->getInstalledVersion();
}
```

## Update Management

```php
// Check for updates
if ($manager->needsUpdate()) {
    $newVersion = $manager->update();
    echo "Updated to: " . $newVersion;
}

// Uninstall
$manager->uninstall();
```

## Executing the Shipper

```php
$result = $manager->execute([
    '--config=/path/to/nadi.yaml',
    '--record',
]);

echo $result['output'];
```

## Platform Detection

The `PlatformDetector` identifies the current system:

```php
use Nadi\Shipper\PlatformDetector;

$detector = new PlatformDetector();

echo $detector->getOS();    // darwin, linux, windows
echo $detector->getArch();  // amd64, 386, arm64

// Get binary name for a version
echo $detector->getBinaryName('v1.0.0');
// Output: shipper-v1.0.0-darwin-arm64.tar.gz

if ($detector->isSupported()) {
    // Platform is supported
}
```

## Version Resolution

The `VersionResolver` retrieves versions from GitHub releases:

```php
use Nadi\Shipper\VersionResolver;

$resolver = new VersionResolver();

$latestVersion = $resolver->getLatestVersion();  // e.g., "v1.0.0"

$downloadUrl = $resolver->getReleaseUrl(
    $latestVersion,
    'shipper-v1.0.0-linux-amd64.tar.gz'
);

$configContent = $resolver->downloadReferenceConfig();
```

## Supported Platforms

| Operating System | Architectures     |
|------------------|-------------------|
| Linux            | amd64, 386, arm64 |
| macOS (Darwin)   | amd64, arm64      |
| Windows          | amd64             |

## Exception Handling

Handle specific error scenarios:

```php
use Nadi\Shipper\BinaryManager;
use Nadi\Shipper\Exceptions\ShipperException;
use Nadi\Shipper\Exceptions\DownloadException;
use Nadi\Shipper\Exceptions\ExtractionException;
use Nadi\Shipper\Exceptions\UnsupportedPlatformException;

try {
    $manager = new BinaryManager('/path/to/bin');
    $manager->install();
} catch (UnsupportedPlatformException $e) {
    // Platform not supported (e.g., Windows 386)
} catch (DownloadException $e) {
    // Network error or GitHub API issue
} catch (ExtractionException $e) {
    // Failed to extract the archive
} catch (ShipperException $e) {
    // General shipper error
}
```

## Next Steps

- [Basic Usage](01-basic-usage.md)
- [Architecture Overview](../01-architecture/01-overview.md)
