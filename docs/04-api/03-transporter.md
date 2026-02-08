# Transporter API

Reference documentation for transporter classes and interfaces.

## Contract Interface

All transporters implement `Nadi\Transporter\Contract`:

```php
namespace Nadi\Transporter;

interface Contract
{
    /**
     * Configure the transporter with options.
     */
    public function configure(array $configurations = []): self;

    /**
     * Get a unique identifier for this transporter instance.
     */
    public function getTransporterId(): string;

    /**
     * Store an entry for later delivery.
     */
    public function store(array $data): self;

    /**
     * Send all stored entries to the backend.
     */
    public function send();

    /**
     * Test the connection to the backend.
     */
    public function test();

    /**
     * Verify the transporter configuration and credentials.
     */
    public function verify();
}
```

### Lifecycle Methods

The core lifecycle follows three steps: `configure()` → `store()` → `send()`.

### Diagnostic Methods

The `test()` and `verify()` methods are diagnostic utilities, not part of the core data flow:

| Method    | Purpose                                         |
|-----------|-------------------------------------------------|
| `test()`  | Tests connectivity to the backend               |
| `verify()`| Verifies configuration and credentials are valid |

Both return `true` on success and `false` on failure for most transporters.

## Service Class

Combines a transporter with a sampling manager. Located at `Nadi\Transporter\Service`.

```php
namespace Nadi\Transporter;

class Service
{
    public function __construct(Contract $transporter, SamplingManager $samplingManager);

    /**
     * Store data only if sampling allows it.
     */
    public function handle(array $data = []);

    public function test();
    public function verify();
    public function send();
}
```

The `handle()` method checks `SamplingManager::shouldSample()` before storing data.
If sampling rejects the data, it is silently dropped.

**Example:**

```php
use Nadi\Transporter\Http;
use Nadi\Transporter\Service;
use Nadi\Sampling\FixedRateSampling;
use Nadi\Sampling\SamplingManager;
use Nadi\Sampling\Config;

$transporter = (new Http())->configure([
    'apiKey' => 'your-api-key',
    'appKey' => 'your-app-key',
]);

$config = new Config(samplingRate: 0.5);
$sampling = new SamplingManager(new FixedRateSampling($config));

$service = new Service($transporter, $sampling);
$service->handle($entry->toArray()); // only stored if sampling allows
$service->send();
```

## Http Transporter

Send entries to the Nadi API. Located at `Nadi\Transporter\Http`.

```php
namespace Nadi\Transporter;

class Http implements Contract
{
    const VERSION = 'v1';
    const ENDPOINT = 'https://nadi.pro/api';

    public function configure(array $configurations = []): self;
    public function getTransporterId(): string;
    public function store(array $data): self;
    public function send();
    public function test();
    public function verify();

    // HTTP-specific methods
    public function setClient(Client $client): self;
    public function getClient(): Client;
    public function url(string $endpoint): string;
}
```

**Configuration Options:**

| Option     | Type   | Default                  | Description                           |
|------------|--------|--------------------------|---------------------------------------|
| `apiKey`   | string | —                        | Bearer token (NADI_API_KEY, required) |
| `appKey`   | string | —                        | Application identifier (NADI_APP_KEY, required) |
| `endpoint` | string | `https://nadi.pro/api`   | API endpoint URL                      |
| `version`  | string | `v1`                     | API version                           |

Alternative keys: `api_key` for `apiKey`, `app_key` or `token` (deprecated) for `appKey`.

**Headers Sent:**

| Header                | Value                       |
|-----------------------|-----------------------------|
| `Authorization`       | `Bearer {apiKey}`           |
| `Nadi-App-Token`      | `{appKey}`                  |
| `Nadi-API-Version`    | `{version}`                 |
| `Nadi-Transporter-Id` | `{transporterId}`           |
| `Accept`              | `application/json`          |
| `Content-Type`        | `application/json`          |

**Diagnostic Methods:**

| Method     | Action                          | Success | Failure |
|------------|---------------------------------|---------|---------|
| `test()`   | POST to `{endpoint}/test`       | `true`  | throws  |
| `verify()` | POST to `{endpoint}/verify`     | `true`  | throws  |

Both expect HTTP 200 responses.

## Log Transporter

Write entries to local JSON files. Located at `Nadi\Transporter\Log`.

```php
namespace Nadi\Transporter;

class Log implements Contract
{
    public function configure(array $configurations = []): self;
    public function getTransporterId(): string;
    public function store(array $data): self;
    public function send();
    public function test();
    public function verify();

    // Log-specific methods
    public function setPath($path);
    public function getPath(): string;
    public function getFileName(): string;
    public function getFilePath(): string;
    public function defaultPath(): string;
    public function log(string $key, array $data = []);
}
```

**Configuration Options:**

| Option | Type   | Default                    | Description                 |
|--------|--------|----------------------------|-----------------------------|
| `path` | string | `{package}/storage/logs`   | Directory for log files     |

**File Output:**

- Entries are stored as JSON in `{transporterId}.json`
- Log messages are written to `nadi-{date}.log`
- The directory is auto-created with a `.gitignore` if it does not exist

**Diagnostic Methods:**

| Method     | Action                                      | Success | Failure |
|------------|---------------------------------------------|---------|---------|
| `test()`   | Checks if the log directory exists           | `true`  | `false` |
| `verify()` | Writes a verification log and reads it back  | `true`  | `false` |

## OpenTelemetry Transporter

Export entries as OTLP spans. Located at `Nadi\Transporter\OpenTelemetry`.

```php
namespace Nadi\Transporter;

class OpenTelemetry implements Contract
{
    public function configure(array $configurations = []): self;
    public function getTransporterId(): string;
    public function store(array $data): self;
    public function send();
    public function test();
    public function verify();
}
```

**Configuration Options:**

| Option                   | Type            | Default                | Description                     |
|--------------------------|-----------------|------------------------|---------------------------------|
| `endpoint`               | string          | `http://localhost:4318` | OTLP endpoint URL               |
| `service_name`           | string          | `nadi-php`             | Service identifier              |
| `service_version`        | string          | `1.0.0`                | Service version                 |
| `suppress_errors`        | bool            | `true`                 | Suppress OTLP export errors     |
| `logger`                 | LoggerInterface | `NullLogger`           | PSR-3 logger for error output   |
| `deployment_environment` | string          | `production`           | Deployment environment label    |

**Diagnostic Methods:**

| Method     | Action                               | Success | Failure |
|------------|--------------------------------------|---------|---------|
| `test()`   | Exports a `nadi.test` span           | `true`  | `false` |
| `verify()` | Exports a `nadi.verify` span with service metadata | `true`  | `false` |

## Tcp Transporter

Send entries over a persistent TCP socket using NDJSON framing. Located at `Nadi\Transporter\Tcp`.

```php
namespace Nadi\Transporter;

class Tcp implements Contract
{
    const PORT = 7430;

    public function configure(array $configurations = []): self;
    public function getTransporterId(): string;
    public function store(array $data): self;
    public function send();
    public function test();
    public function verify();
    public function setSocket($socket): self;
    public function disconnect(): void;
}
```

**Configuration Options:**

| Option       | Type   | Default | Description                          |
|--------------|--------|---------|--------------------------------------|
| `host`       | string | —       | TCP server hostname/IP (required)    |
| `port`       | int    | `7430`  | TCP server port                      |
| `timeout`    | int    | `30`    | Connection/write timeout in seconds  |
| `persistent` | bool   | `true`  | Use persistent socket connections    |

**Return Values:**

| Method     | Success | Failure | Notes                            |
|------------|---------|---------|----------------------------------|
| `send()`   | `true`  | `true`  | Never breaks the monitored app   |
| `test()`   | `true`  | `false` | Sends `nadi.test` payload        |
| `verify()` | `true`  | `false` | Sends `nadi.verify` payload      |

**Testing Support:**

Use `setSocket($socket)` to inject a mock stream resource for unit testing without a real TCP server.

## SilentTransportWrapper

Internal wrapper used by the `OpenTelemetry` transporter to suppress stderr output during OTLP exports. Located at `Nadi\Transporter\SilentTransportWrapper`.

> **Note**: This class implements `OpenTelemetry\SDK\Common\Export\TransportInterface`,
> not `Nadi\Transporter\Contract`. It is not a general-purpose transporter wrapper.

```php
namespace Nadi\Transporter;

class SilentTransportWrapper implements TransportInterface
{
    public function __construct(TransportInterface $transport, LoggerInterface $logger);
    public function contentType(): string;
    public function send(string $payload, ?CancellationInterface $cancellation = null): FutureInterface;
    public function shutdown(?CancellationInterface $cancellation = null): bool;
    public function forceFlush(?CancellationInterface $cancellation = null): bool;
}
```

Behavior:

- Suppresses PHP error output during transport operations
- Logs failures via the provided PSR-3 logger at `debug` level
- Re-throws exceptions after logging to maintain the OTel transport contract

## TransporterException

Exception class with static factory methods for credential validation. Located at `Nadi\Exceptions\TransporterException`.

```php
namespace Nadi\Exceptions;

class TransporterException extends \Exception
{
    public static function throwIfMissingApiKey($apiKey = null): void;
    public static function throwIfMissingAppKey($appKey = null): void;
    public static function throwIfMissingAppCredentials($apiKey, $appKey): void;
    public static function throwIfMissingHost($host = null): void;
    public static function throwIfMissingPort($port = null): void;
    public static function throwIfMissingTcpCredentials($host, $port): void;
}
```

**Exception Messages:**

| Method                         | Message                                                              |
|--------------------------------|----------------------------------------------------------------------|
| `throwIfMissingApiKey`         | Missing API Key (NADI_API_KEY). This is the Sanctum personal access token. |
| `throwIfMissingAppKey`         | Missing App Key (NADI_APP_KEY). This is the application identifier token. |
| `throwIfMissingHost`           | Missing TCP host. A hostname or IP address is required.              |
| `throwIfMissingPort`           | Missing TCP port. A port number is required.                         |

## InteractsWithTransporterId Trait

Generates a unique transporter identifier. Located at `Nadi\Concerns\InteractsWithTransporterId`.

```php
namespace Nadi\Concerns;

trait InteractsWithTransporterId
{
    public function getTransporterId(): string;
}
```

Returns a 64-character random hash string generated from concatenated SHA1 digests. The value
is cached per instance so subsequent calls return the same identifier.

## Next Steps

- [Data API](04-data.md)
- [Transporters Guide](../03-usage/04-transporters.md)
