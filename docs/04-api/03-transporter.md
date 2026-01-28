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
    public function configure(array $options): self;

    /**
     * Store an entry for later delivery.
     */
    public function store(array $data): self;

    /**
     * Send all stored entries.
     */
    public function send(): void;
}
```

## Http Transporter

Send entries to the Nadi API.

```php
namespace Nadi\Transporter;

class Http implements Contract
{
    public function configure(array $options): self;
    public function store(array $data): self;
    public function send(): void;
}
```

**Configuration Options:**

| Option     | Type   | Description                          |
|------------|--------|--------------------------------------|
| `endpoint` | string | API endpoint URL                     |
| `apiKey`   | string | Bearer token (NADI_API_KEY)          |
| `appKey`   | string | Application identifier (NADI_APP_KEY)|

Alternative keys: `api_key`, `app_key`, `token`.

**Headers Sent:**

- `Authorization: Bearer {apiKey}`
- `Nadi-App-Token: {appKey}`
- `Nadi-Transporter-Id: {hash}`

## Log Transporter

Write entries to local files.

```php
namespace Nadi\Transporter;

class Log implements Contract
{
    public function configure(array $options): self;
    public function store(array $data): self;
    public function send(): void;
}
```

**Configuration Options:**

| Option | Type   | Description            |
|--------|--------|------------------------|
| `path` | string | Directory for log files|

## OpenTelemetry Transporter

Export entries using OTLP.

```php
namespace Nadi\Transporter;

class OpenTelemetry implements Contract
{
    public function configure(array $options): self;
    public function store(array $data): self;
    public function send(): void;
}
```

**Configuration Options:**

| Option            | Type   | Description        |
|-------------------|--------|--------------------|
| `endpoint`        | string | OTLP endpoint URL  |
| `service_name`    | string | Service identifier |
| `service_version` | string | Service version    |

## SilentTransportWrapper

Suppress exceptions during transport.

```php
namespace Nadi\Transporter;

class SilentTransportWrapper implements Contract
{
    public function __construct(Contract $transporter);
    public function configure(array $options): self;
    public function store(array $data): self;
    public function send(): void;
}
```

## TransporterException

Exception class with factory methods.

```php
namespace Nadi\Exceptions;

class TransporterException extends \Exception
{
    public static function throwIfMissingApiKey($apiKey = null): void;
    public static function throwIfMissingAppKey($appKey = null): void;
    public static function throwIfMissingAppCredentials($apiKey, $appKey): void;
}
```

## InteractsWithTransporterId Trait

Generates consistent transporter identification.

```php
namespace Nadi\Concerns;

trait InteractsWithTransporterId
{
    public function getTransporterId(): string;
}
```

Returns a SHA256 hash for tracking and deduplication.

## Next Steps

- [Data API](04-data.md)
- [Transporters Guide](../03-usage/04-transporters.md)
