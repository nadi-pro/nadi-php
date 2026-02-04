# Transport Architecture

The transport layer handles delivery of telemetry data to various backends using a consistent lifecycle pattern.

## Design Pattern

All transporters implement the `Contract` interface:

```php
interface Contract
{
    public function configure(array $configurations = []): self;
    public function getTransporterId(): string;
    public function store(array $data): self;
    public function send();
    public function test();
    public function verify();
}
```

### Lifecycle Methods

The core data flow follows three steps:

1. `configure()` - Set up credentials and options
2. `store()` - Add entries to the batch
3. `send()` - Deliver batched entries to the backend

### Diagnostic Methods

Two additional methods exist for connection validation:

| Method     | Purpose                                          |
|------------|--------------------------------------------------|
| `test()`   | Tests connectivity to the backend                |
| `verify()` | Verifies configuration and credentials are valid |

These are not part of the core data flow and are used for health checks and setup validation.

## Service Orchestration

The `Service` class combines a transporter with a `SamplingManager` to control whether data is stored:

```php
$service = new Service($transporter, $samplingManager);
$service->handle($data); // stores only if sampling allows
$service->send();
```

This decouples sampling logic from transport logic.

## HTTP Transporter

The `Http` transporter sends data to the Nadi API:

```php
use Nadi\Transporter\Http;

$transporter = new Http();
$transporter->configure([
    'apiKey' => 'your-api-key',      // NADI_API_KEY - Bearer token
    'appKey' => 'your-app-key',      // NADI_APP_KEY - Application identifier
]);
```

**Headers sent:**

- `Authorization: Bearer {apiKey}` - Sanctum authentication
- `Nadi-App-Token: {appKey}` - Application identifier
- `Nadi-API-Version: {version}` - API version
- `Nadi-Transporter-Id: {hash}` - Transporter instance ID

## Log Transporter

The `Log` transporter writes entries to local JSON files:

```php
use Nadi\Transporter\Log;

$transporter = new Log();
$transporter->configure([
    'path' => '/var/log/nadi',
]);
```

Useful for development, debugging, or offline collection.

## OpenTelemetry Transporter

The `OpenTelemetry` transporter exports data as OTLP spans:

```php
use Nadi\Transporter\OpenTelemetry;

$transporter = new OpenTelemetry();
$transporter->configure([
    'endpoint' => 'http://localhost:4318',
    'service_name' => 'my-app',
    'service_version' => '1.0.0',
]);
```

Uses `SilentTransportWrapper` internally to suppress stderr output from the OTel SDK
when `suppress_errors` is enabled (the default).

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

An internal component used by the `OpenTelemetry` transporter to suppress stderr output during OTLP exports.

> **Note**: This class implements `OpenTelemetry\SDK\Common\Export\TransportInterface`,
> not `Nadi\Transporter\Contract`. It wraps OTel transports specifically, not generic
> Nadi transporters.

## Transporter ID

The `InteractsWithTransporterId` trait generates a unique 64-character hash for each transporter
instance, used for tracking and deduplication. The value is generated once and cached for the
lifetime of the instance.

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
