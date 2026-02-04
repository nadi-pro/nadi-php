# Nadi PHP Client

[![Latest Version](https://img.shields.io/github/v/release/nadi-pro/nadi-php?style=flat-square)](https://github.com/nadi-pro/nadi-php/releases) [![License](https://img.shields.io/github/license/nadi-pro/nadi-php?style=flat-square)](LICENSE) [![CI](https://img.shields.io/github/actions/workflow/status/nadi-pro/nadi-php/run-tests.yml?style=flat-square)](https://github.com/nadi-pro/nadi-php/actions) [![Packagist Version](https://img.shields.io/packagist/v/nadi-pro/nadi-php?style=flat-square)](https://packagist.org/packages/nadi-pro/nadi-php) [![Packagist Downloads](https://img.shields.io/packagist/dt/nadi-pro/nadi-php?style=flat-square)](https://packagist.org/packages/nadi-pro/nadi-php) [![PHP Version](https://img.shields.io/packagist/dependency-v/nadi-pro/nadi-php/php?style=flat-square)](https://packagist.org/packages/nadi-pro/nadi-php)

A PHP SDK for application crash/error monitoring with built-in OpenTelemetry support for industry-standard observability.

## Documentation

Full documentation is available in the [docs/](docs/README.md) directory:

- [Architecture](docs/01-architecture/README.md) - System design and patterns
- [Development](docs/02-development/README.md) - Developer guides and testing
- [Usage](docs/03-usage/README.md) - Practical integration guides
- [API Reference](docs/04-api/README.md) - Class and interface documentation

## Requirements

- PHP 8.1 or higher
- Composer

## Installation

```bash
composer require nadi-pro/nadi-php
```

## Features

- 🔭 **OpenTelemetry Integration**: Built-in support for OTel semantic conventions
- 📊 **Rich Metrics**: Comprehensive system, runtime, network, and custom metrics
- 🎯 **Smart Sampling**: Multiple sampling strategies including fixed-rate, interval-based, peak-load, and dynamic
- 🚀 **Multiple Transporters**: HTTP API, Local Logging, and OpenTelemetry exporters
- 🔄 **Trace Correlation**: Automatic trace context capture for distributed tracing
- 📦 **Shipper Binary Manager**: Auto-download and manage the Nadi Shipper binary

## OpenTelemetry Support

Nadi PHP SDK now includes first-class support for OpenTelemetry (OTel), enabling seamless
integration with modern observability platforms like Jaeger, Prometheus, Grafana, Datadog,
and New Relic.

### OpenTelemetry Transporter

Configure Nadi to export telemetry data using the OpenTelemetry Protocol (OTLP):

```php
use Nadi\Transporter\OpenTelemetry;
use Nadi\Data\Entry;
use Nadi\Data\Type;

// Configure OpenTelemetry transporter
$transporter = new OpenTelemetry();
$transporter->configure([
    'endpoint' => 'http://localhost:4318',  // OTLP endpoint
    'service_name' => 'my-php-app',
    'service_version' => '1.0.0',
]);

// Create an entry
$entry = Entry::make(Type::EXCEPTION, [
    'class' => 'RuntimeException',
    'message' => 'An error occurred',
    'file' => __FILE__,
    'line' => __LINE__,
]);

// Store and send
$transporter->store($entry->toArray());
$transporter->send();
```

### OpenTelemetry Semantic Conventions

All metrics now follow OTel semantic conventions for consistency and interoperability:

#### System Metrics

```php
use Nadi\Metric\System;

$system = new System();
$metrics = $system->toArray();

// Returns OTel-compliant metrics:
// - system.cpu.load_average.1m
// - system.cpu.load_average.5m
// - system.cpu.load_average.15m
// - system.cpu.logical_count
// - system.memory.usage
// - system.memory.limit
// - system.memory.peak
// - system.filesystem.usage
// - system.filesystem.available
// - system.filesystem.total
```

#### Runtime Metrics

```php
use Nadi\Metric\Runtime;

$runtime = new Runtime();
$metrics = $runtime->toArray();

// Returns:
// - process.runtime.name: 'php'
// - process.runtime.version: '8.3.0'
// - process.runtime.description: 'PHP 8.3.0'
// - process.pid: 12345
```

#### Operating System Metrics

```php
use Nadi\Metric\OperatingSystem;

$os = new OperatingSystem();
$metrics = $os->toArray();

// Returns:
// - os.type: 'darwin'
// - os.description: 'Darwin 23.0.0'
// - os.name: 'Darwin'
// - os.version: '23.0.0'
// - host.name: 'macbook-pro.local'
// - host.arch: 'x86_64'
```

#### Network Metrics

```php
use Nadi\Metric\Network;

$network = new Network();
$metrics = $network->toArray();

// Returns:
// - host.name: 'server-01'
// - host.id: 'unique-machine-id'
```

### Trace Context Correlation

Nadi automatically captures OpenTelemetry trace context for distributed tracing:

```php
use Nadi\Data\Entry;
use Nadi\Data\Type;

// Entry automatically captures active OTel span context
$entry = Entry::make(Type::EXCEPTION, [
    'class' => 'DatabaseException',
    'message' => 'Connection failed',
]);

// Or set manually
$entry->setTraceId('4bf92f3577b34da6a3ce929d0e0e4736');
$entry->setSpanId('00f067aa0ba902b7');

// Trace IDs are included in exported data
$data = $entry->toArray();
// Contains: trace_id, span_id
```

### Connecting to Observability Backends

#### Jaeger

```php
$transporter->configure([
    'endpoint' => 'http://jaeger:4318',
    'service_name' => 'my-app',
]);
```

#### Grafana Tempo

```php
$transporter->configure([
    'endpoint' => 'http://tempo:4318',
    'service_name' => 'my-app',
]);
```

#### Local Development (Jaeger All-in-One)

```bash
# Run Jaeger
docker run -d --name jaeger \
  -p 4318:4318 \
  -p 16686:16686 \
  jaegertracing/all-in-one:latest

# Access UI at http://localhost:16686
```

## Testing

Run the test suite:

```bash
composer test
```

**Note:** OpenTelemetry tests may show connection warnings if Jaeger is not running locally.
These are expected and the tests will still pass. To run tests with a live OTLP endpoint,
start Jaeger first:

```bash
# Start Jaeger for testing
docker run -d --name jaeger -p 4318:4318 -p 16686:16686 jaegertracing/all-in-one:latest

# Run tests
composer test

# Stop Jaeger
docker stop jaeger && docker rm jaeger
```

## Adding New Metric

You can add a new metric as you see fit to your application / framework.

Do take note, all metrics will be converted to associative array.

In order to create your own metrics, you need to extend the class `Nadi\Metric\Base` and implement
your metrics details in `metrics()` method which always returns an array. You may need to define
as a dot notation in your metric.

However, Nadi will convert to the associative array.

Following is an example for capture Http request for Laravel framework.

```php
<?php

namespace App\Metric;

use Nadi\Support\Arr;
use Nadi\Metric\Base;
use Illuminate\Support\Str;

class Http extends Base
{
    public function metrics(): array
    {
        $startTime = defined('LARAVEL_START') ? LARAVEL_START : request()->server('REQUEST_TIME_FLOAT');

        return [
            'http.client.duration' => $startTime ? floor((microtime(true) - $startTime) * 1000) : null,
            'http.scheme' => request()->getScheme(),
            'http.route' => request()->getRequestUri(),
            'http.method' => request()->getMethod(),
            'http.status_code' => http_response_code(),
            'http.query' => request()->getQueryString(),
            'http.uri' => str_replace(request()->root(), '', request()->fullUrl()) ?: '/',
            'http.headers' => Arr::undot(collect(request()->headers->all())
                ->map(function ($header) {
                    return $header[0];
                })
                ->reject(function ($header, $key) {
                    return in_array($key, [
                        'authorization', config('nadi.header-key'), 'nadi-key',
                    ]);
                })
                ->toArray()),
        ];
    }
}
```

Once you have declared your metric, you can use in your application:

```php
use App\Metrics\Http;
use Nadi\Metric\Metric;

$metric = new Metric();

$metric->add(new Http());

$metric->toArray();
```

If you are adding from Laravel framework, you can simply just add in `config/nadi.php`:

```php
'metrics' => [
    \App\Metrics\Http::class,
];
```

## Class Diagram

![Nadi PHP UML Class Diagram](nadi-php-uml-diagram.png)

## Sampling

Following are the sampling strategy provided by default:

1. [Base Sampling](src/Sampling/BaseSampling.php)
2. [Fix Rate Sampling](src/Sampling/FixedRateSampling.php)
3. [Interval Sampling](src/Sampling/IntervalSampling.php)
4. [Peak Load Sampling](src/Sampling/PeakLoadSampling.php)
5. [Dynamic Rate Sampling](src/Sampling/DynamicRateSampling.php)

### Usage

The Sample [Config](src/Sampling/Config.php) can be construct as following:

```php
use Nadi\Sampling\Config;

$config = new Config(
    samplingRate: 0.1,
    baseRate: 0.05,
    loadFactor: 1.0,
    intervalSeconds: 60
);
```

Then based on available sampling strategy, contruct the sampling object:

```php
use Nadi\Sampling\FixedRateSampling;

$samplingStrategy = new FixedRateSampling($config);
```

You can use directly the sampling:

```php
if($samplingStrategy->shouldSample()) {
    // do something
}
```

Or you require [Sampling Manager](src/Sampling/SamplingManager.php):

```php
use Nadi\Sampling\SamplingManager;

$samplingManager = new SamplingManager($samplingStrategy);

if($samplingManager->shouldSample()) {
    // do something
}
```

> Use Sampling Manager if you rely on dynamic use of sampling stategy.

### Create Your Own Sample Strategy

To create your own sampling strategy:

```php
namespace App\Sampling;

use Nadi\Sampling\Contract;
use Nadi\Sampling\Config;

class CustomSampling implements Contract
{
    public function __construct(protected Config $config) {}

    public function shouldSample(): bool
    {
        // do your logic hhere

        return true;
    }
}
```

## Shipper Binary Manager

The SDK includes a shared library for managing the Nadi Shipper binary. This allows automatic
downloading and installation of the shipper binary across different PHP packages (Laravel,
WordPress, etc.).

### Supported Platforms

| Operating System | Architectures |
|-----------------|---------------|
| Linux | amd64, 386, arm64 |
| macOS (Darwin) | amd64, arm64 |
| Windows | amd64 |

### Basic Usage

```php
use Nadi\Shipper\BinaryManager;

// Create manager with target directory
$manager = new BinaryManager('/path/to/bin');

// Install latest version
$binaryPath = $manager->install();

// Check if installed
if ($manager->isInstalled()) {
    echo "Shipper installed at: " . $manager->getBinaryPath();
    echo "Version: " . $manager->getInstalledVersion();
}

// Check for updates
if ($manager->needsUpdate()) {
    $newVersion = $manager->update();
    echo "Updated to: " . $newVersion;
}

// Execute shipper
$result = $manager->execute(['--config=/path/to/nadi.yaml', '--record']);
echo $result['output'];

// Uninstall
$manager->uninstall();
```

### Components

#### PlatformDetector

Detects the current operating system and architecture:

```php
use Nadi\Shipper\PlatformDetector;

$detector = new PlatformDetector();

echo $detector->getOS();           // darwin, linux, windows
echo $detector->getArch();         // amd64, 386, arm64
echo $detector->getBinaryName('v1.0.0');  // shipper-v1.0.0-darwin-arm64.tar.gz

if ($detector->isSupported()) {
    // Platform is supported
}
```

#### VersionResolver

Resolves versions from GitHub releases:

```php
use Nadi\Shipper\VersionResolver;

$resolver = new VersionResolver();

$latestVersion = $resolver->getLatestVersion();  // e.g., "v1.0.0"
$downloadUrl = $resolver->getReleaseUrl($latestVersion, 'shipper-v1.0.0-linux-amd64.tar.gz');
$configContent = $resolver->downloadReferenceConfig();
```

### Exception Handling

The library uses specific exceptions for different error scenarios:

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
