# Transporters

Configure and use the available transporters to deliver telemetry data.

## HTTP Transporter

Send data to the Nadi API:

```php
use Nadi\Transporter\Http;
use Nadi\Data\Entry;
use Nadi\Data\Type;

$transporter = new Http();
$transporter->configure([
    'endpoint' => 'https://api.nadi.pro/api/entries',
    'apiKey' => env('NADI_API_KEY'),
    'appKey' => env('NADI_APP_KEY'),
]);

$entry = Entry::make(Type::EXCEPTION, [
    'class' => 'RuntimeException',
    'message' => 'An error occurred',
]);

$transporter->store($entry->toArray());
$transporter->send();
```

### Configuration Options

| Option     | Description                                     |
|------------|-------------------------------------------------|
| `endpoint` | API endpoint URL                                |
| `apiKey`   | Bearer token for authentication (NADI_API_KEY)  |
| `appKey`   | Application identifier (NADI_APP_KEY)           |

### Alternative Key Names

The HTTP transporter accepts multiple key formats:

```php
// Standard
['apiKey' => '...', 'appKey' => '...']

// Snake case
['api_key' => '...', 'app_key' => '...']

// Legacy
['token' => '...']  // For appKey
```

## Log Transporter

Write entries to local files for development or debugging:

```php
use Nadi\Transporter\Log;

$transporter = new Log();
$transporter->configure([
    'path' => '/var/log/nadi',
]);

$transporter->store($entry->toArray());
$transporter->send();
```

Log files are JSON-formatted for easy parsing.

## OpenTelemetry Transporter

Export data using OTLP:

```php
use Nadi\Transporter\OpenTelemetry;

$transporter = new OpenTelemetry();
$transporter->configure([
    'endpoint' => 'http://localhost:4318',
    'service_name' => 'my-app',
    'service_version' => '1.0.0',
]);

$transporter->store($entry->toArray());
$transporter->send();
```

See [OpenTelemetry Guide](05-opentelemetry.md) for detailed integration.

## Silent Transport Wrapper

Suppress exceptions during transport:

```php
use Nadi\Transporter\SilentTransportWrapper;
use Nadi\Transporter\Http;

$http = new Http();
$http->configure([...]);

$transporter = new SilentTransportWrapper($http);

// Exceptions are caught and logged, not thrown
$transporter->store($entry->toArray());
$transporter->send();
```

Useful for production environments where transport failures should not affect the application.

## Batching Entries

Store multiple entries before sending:

```php
$transporter->store($entry1->toArray());
$transporter->store($entry2->toArray());
$transporter->store($entry3->toArray());

// Send all batched entries
$transporter->send();
```

## Next Steps

- [OpenTelemetry Integration](05-opentelemetry.md)
- [Transport Architecture](../01-architecture/04-transport.md)
