# Sampling Architecture

The sampling layer controls when telemetry data should be captured, reducing overhead and cost
while maintaining representative data.

## Design Pattern

All sampling strategies implement the `Contract` interface:

```php
interface Contract
{
    public function shouldSample(): bool;
}
```

## Configuration

The `Config` class holds sampling parameters:

```php
use Nadi\Sampling\Config;

$config = new Config(
    samplingRate: 0.1,        // 10% sampling rate
    baseRate: 0.05,           // 5% base rate for dynamic sampling
    loadFactor: 1.0,          // Load multiplier
    intervalSeconds: 60       // Interval between samples
);
```

## Built-in Strategies

### BaseSampling

Simple random sampling based on `samplingRate`:

```php
use Nadi\Sampling\BaseSampling;

$sampler = new BaseSampling($config);
// Returns true ~10% of the time (based on samplingRate)
```

### FixedRateSampling

Consistent fixed-rate sampling:

```php
use Nadi\Sampling\FixedRateSampling;

$sampler = new FixedRateSampling($config);
// Samples at exactly the configured rate
```

### IntervalSampling

Time-based sampling that allows one sample per interval:

```php
use Nadi\Sampling\IntervalSampling;

$sampler = new IntervalSampling($config);
// Allows one sample every 60 seconds (based on intervalSeconds)
```

### PeakLoadSampling

Adjusts sampling based on system load:

```php
use Nadi\Sampling\PeakLoadSampling;

$sampler = new PeakLoadSampling($config);
// Reduces sampling during high load periods
```

### DynamicRateSampling

Combines multiple factors for adaptive sampling:

```php
use Nadi\Sampling\DynamicRateSampling;

$sampler = new DynamicRateSampling($config);
// Adjusts rate based on baseRate and loadFactor
```

## SamplingManager

The `SamplingManager` wraps strategies for dynamic switching:

```php
use Nadi\Sampling\SamplingManager;
use Nadi\Sampling\FixedRateSampling;

$strategy = new FixedRateSampling($config);
$manager = new SamplingManager($strategy);

if ($manager->shouldSample()) {
    // Process telemetry
}

// Switch strategy at runtime
$manager->setStrategy(new IntervalSampling($config));
```

## Strategy Selection Guide

| Strategy            | Use Case                                  |
|---------------------|-------------------------------------------|
| BaseSampling        | General purpose, simple random sampling   |
| FixedRateSampling   | Consistent sampling rate requirements     |
| IntervalSampling    | Rate limiting, one sample per time window |
| PeakLoadSampling    | High-traffic applications, load-sensitive |
| DynamicRateSampling | Adaptive sampling based on conditions     |

## Next Steps

- [Creating Custom Strategies](../03-usage/03-custom-sampling.md)
- [Transport Layer](04-transport.md)
