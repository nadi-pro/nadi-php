# Custom Sampling

Implement custom sampling strategies for application-specific requirements.

## Creating a Custom Strategy

Implement `Nadi\Sampling\Contract`:

```php
namespace App\Sampling;

use Nadi\Sampling\Contract;
use Nadi\Sampling\Config;

class CustomSampling implements Contract
{
    public function __construct(protected Config $config)
    {
    }

    public function shouldSample(): bool
    {
        // Your sampling logic here
        return true;
    }
}
```

## Configuration Parameters

The `Config` class provides these parameters:

```php
use Nadi\Sampling\Config;

$config = new Config(
    samplingRate: 0.1,        // Primary sampling rate (0.0 - 1.0)
    baseRate: 0.05,           // Base rate for adaptive sampling
    loadFactor: 1.0,          // Load multiplier
    intervalSeconds: 60       // Time interval in seconds
);
```

Access in your strategy:

```php
public function shouldSample(): bool
{
    $rate = $this->config->samplingRate;
    $base = $this->config->baseRate;
    $load = $this->config->loadFactor;
    $interval = $this->config->intervalSeconds;

    // Use values in your logic
}
```

## Example: Error-Based Sampling

Sample more frequently when errors occur:

```php
namespace App\Sampling;

use Nadi\Sampling\Contract;
use Nadi\Sampling\Config;

class ErrorBasedSampling implements Contract
{
    private int $errorCount = 0;

    public function __construct(protected Config $config)
    {
    }

    public function recordError(): void
    {
        $this->errorCount++;
    }

    public function shouldSample(): bool
    {
        // Higher sampling rate when errors are occurring
        $rate = $this->config->samplingRate;

        if ($this->errorCount > 10) {
            $rate = min(1.0, $rate * 5);  // 5x sampling during errors
        }

        return (mt_rand() / mt_getrandmax()) < $rate;
    }
}
```

## Example: Priority-Based Sampling

Always sample certain request types:

```php
namespace App\Sampling;

use Nadi\Sampling\Contract;
use Nadi\Sampling\Config;

class PrioritySampling implements Contract
{
    private array $alwaysSamplePaths = [
        '/api/checkout',
        '/api/payment',
    ];

    public function __construct(protected Config $config)
    {
    }

    public function shouldSample(): bool
    {
        $path = request()->path();

        // Always sample critical paths
        foreach ($this->alwaysSamplePaths as $priority) {
            if (str_starts_with($path, $priority)) {
                return true;
            }
        }

        // Normal sampling for other paths
        return (mt_rand() / mt_getrandmax()) < $this->config->samplingRate;
    }
}
```

## Using SamplingManager

Wrap your strategy with `SamplingManager` for dynamic switching:

```php
use Nadi\Sampling\SamplingManager;
use App\Sampling\CustomSampling;

$strategy = new CustomSampling($config);
$manager = new SamplingManager($strategy);

if ($manager->shouldSample()) {
    // Process telemetry
}

// Switch strategies at runtime
$manager->setStrategy(new PrioritySampling($config));
```

## Next Steps

- [Transporters](04-transporters.md)
- [Sampling Architecture](../01-architecture/03-sampling.md)
