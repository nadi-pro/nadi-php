# Sampling API

Reference documentation for sampling strategy classes and interfaces.

## Contract Interface

All sampling strategies implement `Nadi\Sampling\Contract`:

```php
namespace Nadi\Sampling;

interface Contract
{
    /**
     * Determine if this request should be sampled.
     */
    public function shouldSample(): bool;
}
```

## Config Class

Configuration for sampling strategies:

```php
namespace Nadi\Sampling;

class Config
{
    public function __construct(
        public float $samplingRate = 0.1,
        public float $baseRate = 0.05,
        public float $loadFactor = 1.0,
        public int $intervalSeconds = 60
    );
}
```

**Properties:**

| Property          | Type  | Default | Description                              |
|-------------------|-------|---------|------------------------------------------|
| `samplingRate`    | float | 0.1     | Primary sampling rate (0.0 - 1.0)        |
| `baseRate`        | float | 0.05    | Base rate for adaptive sampling          |
| `loadFactor`      | float | 1.0     | Load multiplier for dynamic strategies   |
| `intervalSeconds` | int   | 60      | Time interval for interval-based sampling|

## SamplingManager

Wrapper for dynamic strategy switching:

```php
namespace Nadi\Sampling;

class SamplingManager
{
    public function __construct(Contract $strategy);

    /**
     * Delegate to current strategy.
     */
    public function shouldSample(): bool;

    /**
     * Switch to a different strategy.
     */
    public function setStrategy(Contract $strategy): void;
}
```

## Built-in Strategies

### BaseSampling

Simple random sampling based on `samplingRate`.

```php
$sampler = new BaseSampling($config);
```

### FixedRateSampling

Consistent fixed-rate sampling.

```php
$sampler = new FixedRateSampling($config);
```

### IntervalSampling

Time-based sampling allowing one sample per `intervalSeconds`.

```php
$sampler = new IntervalSampling($config);
```

### PeakLoadSampling

Adjusts sampling based on system load using `loadFactor`.

```php
$sampler = new PeakLoadSampling($config);
```

### DynamicRateSampling

Adaptive sampling using `baseRate` and `loadFactor`.

```php
$sampler = new DynamicRateSampling($config);
```

## Next Steps

- [Transporter API](03-transporter.md)
- [Custom Sampling Guide](../03-usage/03-custom-sampling.md)
