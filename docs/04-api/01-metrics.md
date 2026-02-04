# Metrics API

Reference documentation for metric classes and interfaces.

## Contract Interface

All metrics implement `Nadi\Metric\Contract`:

```php
namespace Nadi\Metric;

interface Contract
{
    /**
     * Return metrics as dot-notation array.
     */
    public function metrics(): array;

    /**
     * Convert metrics to nested associative array.
     */
    public function toArray(): array;
}
```

## Base Class

Abstract base class for all metrics:

```php
namespace Nadi\Metric;

abstract class Base implements Contract
{
    abstract public function metrics(): array;

    public function toArray(): array
    {
        return Arr::undot($this->metrics());
    }
}
```

## Metric Aggregator

Aggregates multiple metrics:

```php
namespace Nadi\Metric;

class Metric
{
    /**
     * Add a custom metric.
     */
    public function add(Contract $metric): void;

    /**
     * Get all aggregated metrics (includes built-in + custom).
     */
    public function toArray(): array;
}
```

Pre-loaded metrics: Browser, Network, OperatingSystem, Runtime, System.

## Built-in Metrics

### Browser

Client browser detection.

**Keys:**

- `browser.name`
- `browser.version`
- `browser.platform`
- `browser.device`
- `browser.is_mobile`
- `browser.is_desktop`
- `browser.is_bot`

### Network

Network identification.

**Keys:**

- `host.name`
- `host.id`

### OperatingSystem

OS and host metrics.

**Keys:**

- `os.type`
- `os.description`
- `os.name`
- `os.version`
- `host.name`
- `host.arch`

### Runtime

PHP runtime information.

**Keys:**

- `process.runtime.name`
- `process.runtime.version`
- `process.runtime.description`
- `process.pid`

### System

System resource metrics.

**Keys:**

- `system.cpu.load_average.1m`
- `system.cpu.load_average.5m`
- `system.cpu.load_average.15m`
- `system.cpu.logical_count`
- `system.memory.usage`
- `system.memory.limit`
- `system.memory.peak`
- `system.filesystem.usage`
- `system.filesystem.available`
- `system.filesystem.total`

## Next Steps

- [Sampling API](02-sampling.md)
- [Custom Metrics Guide](../03-usage/02-custom-metrics.md)
