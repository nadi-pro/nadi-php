# Code Style

Nadi PHP SDK uses Laravel Pint for consistent code formatting.

## Formatting Code

Run the formatter:

```bash
composer format
```

This executes Laravel Pint to format all PHP files in the project.

## Conventions

### Namespaces

Follow PSR-4 autoloading with the `Nadi\` root namespace:

```php
namespace Nadi\Metric;
namespace Nadi\Sampling;
namespace Nadi\Transporter;
namespace Nadi\Data;
namespace Nadi\Support;
namespace Nadi\Shipper;
```

### Interface Naming

Interfaces are named `Contract`:

```php
namespace Nadi\Metric;

interface Contract
{
    public function metrics(): array;
    public function toArray(): array;
}
```

### Exception Factories

Use static factory methods for exception creation:

```php
namespace Nadi\Exceptions;

class TransporterException extends \Exception
{
    public static function throwIfMissingApiKey($apiKey = null)
    {
        if (empty($apiKey)) {
            throw new self('Missing API key.');
        }
    }
}
```

### Fluent Interfaces

Methods that support chaining return `$this`:

```php
public function configure(array $options): self
{
    $this->configurations = $options;
    return $this;
}
```

### Dot-Notation in Metrics

Return dot-notation keys from `metrics()` methods:

```php
public function metrics(): array
{
    return [
        'system.cpu.load_average.1m' => sys_getloadavg()[0],
        'system.memory.usage' => memory_get_usage(true),
    ];
}
```

## Next Steps

- [Getting Started](01-getting-started.md)
- [Architecture Overview](../01-architecture/01-overview.md)
