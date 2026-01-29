# Transport Architecture

The transport layer handles delivery of telemetry data to various backends using a consistent lifecycle pattern.

## Design Pattern

All transporters implement the `Contract` interface with three lifecycle methods:

```php
interface Contract
{
    public function configure(array $options): self;
    public function store(array $data): self;
    public function send(): void;
}
```

**Lifecycle:**

1. `configure()` - Set up credentials and options
2. `store()` - Add entries to the batch
3. `send()` - Deliver batched entries to the backend

## HTTP Transporter

The `Http` transporter sends data to the Nadi API:

```php
use Nadi\Transporter\Http;

$transporter = new Http();
$transporter->configure([
    'endpoint' => 'https://api.nadi.pro/api/entries',
    'apiKey' => 'your-api-key',      // NADI_API_KEY - Bearer token
    'appKey' => 'your-app-key',      // NADI_APP_KEY - Application identifier
]);
```

**Headers sent:**

- `Authorization: Bearer {apiKey}` - Sanctum authentication
- `Nadi-App-Token: {appKey}` - Application identifier
- `Nadi-Transporter-Id: {hash}` - Consistent transporter ID

## Log Transporter

The `Log` transporter writes entries to local files:

```php
use Nadi\Transporter\Log;

$transporter = new Log();
$transporter->configure([
    'path' => '/var/log/nadi',
]);
```

Useful for development, debugging, or offline collection.

## OpenTelemetry Transporter

The `OpenTelemetry` transporter exports data using OTLP:

```php
use Nadi\Transporter\OpenTelemetry;

$transporter = new OpenTelemetry();
$transporter->configure([
    'endpoint' => 'http://localhost:4318',
    'service_name' => 'my-app',
    'service_version' => '1.0.0',
]);
```

**Compatible backends:**

- Jaeger
- Grafana Tempo
- Prometheus
- Datadog
- New Relic

## TCP Transporter

The `Tcp` transporter sends data over a persistent TCP socket using
newline-delimited JSON (NDJSON) framing. It uses only PHP built-in
`stream_socket_client()` with no additional dependencies.

```php
use Nadi\Transporter\Tcp;

$transporter = new Tcp();
$transporter->configure([
    'host' => '127.0.0.1',
    'port' => 7430,          // Default: 7430
    'timeout' => 30,          // Default: 30 seconds
    'persistent' => true,     // Default: true
]);
```

**Design decisions:**

- **Lazy connection**: Socket opens on first `send()`/`test()`/`verify()`, not in `configure()`
- **Persistent sockets**: Uses `STREAM_CLIENT_PERSISTENT` for long-running processes
- **NDJSON framing**: Each entry is serialized as `json_encode($entry) . "\n"`
- **Partial write handling**: Internal loop handles TCP partial writes
- **Single reconnect**: On write failure, disconnects and reconnects once before retrying
- **Graceful error handling**: `send()` returns `true` even on failure to never break the monitored app.
  `test()`/`verify()` return `false` on failure for truthful diagnostics
- **Fail-fast configuration**: `configure()` throws `TransporterException` if host or port is missing

**Compatible receivers:**

- Logstash (TCP input plugin)
- Fluentd (in_tcp plugin)
- Vector (tcp source)
- Custom TCP daemons

## SilentTransportWrapper

Wraps transporters to suppress exceptions:

```php
use Nadi\Transporter\SilentTransportWrapper;

$wrapped = new SilentTransportWrapper($transporter);
// Exceptions are caught and logged, not thrown
```

## Transporter ID

The `InteractsWithTransporterId` trait generates a consistent SHA256 hash for each transporter
instance, used for tracking and deduplication.

## Exception Handling

Use static factory methods for validation:

```php
use Nadi\Exceptions\TransporterException;

// In transporter implementation
TransporterException::throwIfMissingAppCredentials($apiKey, $appKey);
```

Available methods:

- `throwIfMissingApiKey($apiKey)`
- `throwIfMissingAppKey($appKey)`
- `throwIfMissingAppCredentials($apiKey, $appKey)`
- `throwIfMissingHost($host)`
- `throwIfMissingPort($port)`
- `throwIfMissingTcpCredentials($host, $port)`

## Next Steps

- [Using Transporters](../03-usage/04-transporters.md)
- [OpenTelemetry Integration](../03-usage/05-opentelemetry.md)
