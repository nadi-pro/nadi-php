<p align="center">
<a href="https://github.com/nadi-pro/nadi-php/actions"><img src="https://github.com/nadi-pro/nadi-php/actions/workflows/run-tests.yml/badge.svg" alt="Build Status"></a>
</p>

# Nadi PHP Client

Nadi is a simple issue tracker for monitoring your application crashes. This package developed for PHP with built-in OpenTelemetry support for industry-standard observability.

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

## OpenTelemetry Support

Nadi PHP SDK now includes first-class support for OpenTelemetry (OTel), enabling seamless integration with modern observability platforms like Jaeger, Prometheus, Grafana, Datadog, and New Relic.

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

**Note:** OpenTelemetry tests may show connection warnings if Jaeger is not running locally. These are expected and the tests will still pass. To run tests with a live OTLP endpoint, start Jaeger first:

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

In order to create your own metrics, you need to extends the class `Nadi\Metric\Base` and implement your metrics details in `metrics()` method which always return an array. You may need to define as a dot notation in your metric.

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

<center>
<img src="nadi-php-uml-diagram.png">
</center>

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
