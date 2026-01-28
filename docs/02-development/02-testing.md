# Testing

Guide for running and writing tests for Nadi PHP SDK.

## Running Tests

Run the full test suite:

```bash
composer test
```

This executes PHPUnit with the configuration in `phpunit.xml`.

## Test Structure

Tests are located in `tests/Features/`:

| File                                       | Coverage                        |
|--------------------------------------------|---------------------------------|
| `CoreTest.php`                             | Metrics, samplers, transporters |
| `OpenTelemetryTest.php`                    | OTLP exporter connectivity      |
| `OpenTelemetrySemanticConventionsTest.php` | OTel naming conventions         |

## OpenTelemetry Testing

OpenTelemetry tests require a running OTLP endpoint. Start Jaeger for local testing:

```bash
# Start Jaeger
docker run -d --name jaeger \
  -p 4318:4318 \
  -p 16686:16686 \
  jaegertracing/all-in-one:latest

# Run tests
composer test

# Stop Jaeger
docker stop jaeger && docker rm jaeger
```

Access the Jaeger UI at `http://localhost:16686` to view traces.

Without Jaeger running, OpenTelemetry tests will show connection warnings but still pass.

## Writing Tests

Tests extend `Nadi\Tests\TestCase`:

```php
namespace Nadi\Tests\Features;

use Nadi\Tests\TestCase;

class MyFeatureTest extends TestCase
{
    public function test_my_feature()
    {
        // Test implementation
    }
}
```

## Mocking HTTP Requests

Use Guzzle's MockHandler for HTTP transporter tests:

```php
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

$mock = new MockHandler([
    new Response(200, [], json_encode(['success' => true])),
]);

$handlerStack = HandlerStack::create($mock);
$client = new Client(['handler' => $handlerStack]);
```

## Test Environment

The `.env.testing` file sets `ACT=true` for GitHub Actions compatibility.

## Next Steps

- [Code Style](03-code-style.md)
- [Architecture Overview](../01-architecture/01-overview.md)
