# Custom Metrics

Create custom metrics to capture application-specific telemetry data.

## Creating a Custom Metric

Extend `Nadi\Metric\Base` and implement the `metrics()` method:

```php
namespace App\Metrics;

use Nadi\Metric\Base;

class Http extends Base
{
    public function metrics(): array
    {
        $startTime = defined('LARAVEL_START')
            ? LARAVEL_START
            : request()->server('REQUEST_TIME_FLOAT');

        return [
            'http.client.duration' => $startTime
                ? floor((microtime(true) - $startTime) * 1000)
                : null,
            'http.scheme' => request()->getScheme(),
            'http.route' => request()->getRequestUri(),
            'http.method' => request()->getMethod(),
            'http.status_code' => http_response_code(),
            'http.query' => request()->getQueryString(),
            'http.uri' => str_replace(
                request()->root(),
                '',
                request()->fullUrl()
            ) ?: '/',
        ];
    }
}
```

## Dot-Notation Convention

Always return dot-notation keys from `metrics()`. The `toArray()` method automatically converts them to nested arrays:

```php
// metrics() returns:
[
    'http.status_code' => 200,
    'http.method' => 'GET',
]

// toArray() produces:
[
    'http' => [
        'status_code' => 200,
        'method' => 'GET',
    ],
]
```

## OpenTelemetry Semantic Conventions

Follow OTel naming conventions for interoperability:

| Prefix        | Use For                      |
|---------------|------------------------------|
| `http.*`      | HTTP metrics                 |
| `db.*`        | Database metrics             |
| `messaging.*` | Message queue metrics        |
| `rpc.*`       | RPC metrics                  |
| `custom.*`    | Application-specific metrics |

## Registering Custom Metrics

### Programmatic Registration

```php
use Nadi\Metric\Metric;
use App\Metrics\Http;

$metric = new Metric();
$metric->add(new Http());

$data = $metric->toArray();
```

### Laravel Configuration

```php
// config/nadi.php
return [
    'metrics' => [
        \App\Metrics\Http::class,
        \App\Metrics\Database::class,
        \App\Metrics\Cache::class,
    ],
];
```

## Example: Database Query Metric

```php
namespace App\Metrics;

use Nadi\Metric\Base;

class DatabaseQuery extends Base
{
    protected float $duration;
    protected string $query;

    public function __construct(float $duration, string $query)
    {
        $this->duration = $duration;
        $this->query = $query;
    }

    public function metrics(): array
    {
        return [
            'db.system' => 'mysql',
            'db.statement' => $this->query,
            'db.operation.duration' => $this->duration,
        ];
    }
}
```

## Next Steps

- [Custom Sampling](03-custom-sampling.md)
- [Metrics Architecture](../01-architecture/02-metrics.md)
