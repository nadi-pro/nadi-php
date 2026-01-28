# Metrics Architecture

The metrics layer collects telemetry data using a consistent dot-notation pattern that follows OpenTelemetry semantic conventions.

## Design Pattern

All metrics extend `Nadi\Metric\Base` and implement the `Contract` interface:

```php
interface Contract
{
    public function metrics(): array;
    public function toArray(): array;
}
```

The `metrics()` method returns dot-notation keys, and `toArray()` automatically converts them to nested arrays using `Arr::undot()`.

## Built-in Metrics

### System (`src/Metric/System.php`)

CPU, memory, and filesystem metrics:

```text
system.cpu.load_average.1m
system.cpu.load_average.5m
system.cpu.load_average.15m
system.cpu.logical_count
system.memory.usage
system.memory.limit
system.memory.peak
system.filesystem.usage
system.filesystem.available
system.filesystem.total
```

### Runtime (`src/Metric/Runtime.php`)

PHP runtime information:

```text
process.runtime.name          # "php"
process.runtime.version       # "8.3.0"
process.runtime.description   # "PHP 8.3.0"
process.pid                   # Process ID
```

### OperatingSystem (`src/Metric/OperatingSystem.php`)

OS and host metrics:

```text
os.type           # darwin, linux, windows
os.description    # Full OS description
os.name           # OS name
os.version        # OS version
host.name         # Hostname
host.arch         # Architecture (x86_64, arm64)
```

### Network (`src/Metric/Network.php`)

Network identification:

```text
host.name    # Server hostname
host.id      # Unique machine identifier
```

### Browser (`src/Metric/Browser.php`)

Client browser detection (when `request()` is available):

```text
browser.name
browser.version
browser.platform
browser.device
browser.is_mobile
browser.is_desktop
browser.is_bot
```

## Metric Aggregation

The `Metric` class (`src/Metric/Metric.php`) aggregates all metrics:

```php
use Nadi\Metric\Metric;

$metric = new Metric();

// Built-in metrics are pre-loaded:
// - Browser, Network, OperatingSystem, Runtime, System

// Add custom metrics
$metric->add(new CustomMetric());

// Get all metrics as nested array
$data = $metric->toArray();
```

## OpenTelemetry Semantic Conventions

All metrics follow [OTel semantic conventions](https://opentelemetry.io/docs/specs/semconv/):

| Prefix       | Convention          |
|--------------|---------------------|
| `system.*`   | System metrics      |
| `process.*`  | Process metrics     |
| `os.*`       | Operating system    |
| `host.*`     | Host identification |
| `http.*`     | HTTP metrics        |

## Dot-Notation Conversion

The `Arr::undot()` utility converts flat keys to nested arrays:

```php
// Input (dot-notation)
[
    'http.status_code' => 200,
    'http.method' => 'GET',
]

// Output (nested)
[
    'http' => [
        'status_code' => 200,
        'method' => 'GET',
    ],
]
```

## Next Steps

- [Creating Custom Metrics](../03-usage/02-custom-metrics.md)
- [Sampling Strategies](03-sampling.md)
