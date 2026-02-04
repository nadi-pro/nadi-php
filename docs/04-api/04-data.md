# Data API

Reference documentation for data structure classes.

## Entry Class

Core data structure for telemetry entries. Located at `Nadi\Data\Entry`.

### Constructor

```php
public function __construct(string $type, array $content, ?string $uuid = null)
```

| Parameter  | Type         | Description                                   |
|------------|--------------|-----------------------------------------------|
| `$type`    | string       | Entry type constant from `Type` class         |
| `$content` | array        | Entry payload data                            |
| `$uuid`    | string\|null | Custom UUID; auto-generated (v4) if omitted   |

### Static Factory

```php
public static function make(string $type, array $content, ?string $uuid = null): static
```

Creates a new entry instance. Accepts the same parameters as the constructor.

### Methods

```php
// Trace context
public function setTraceId(string $traceId): self;
public function getTraceId(): ?string;
public function setSpanId(string $spanId): self;
public function getSpanId(): ?string;

// Runtime
public function setRuntime(string $runtime): self;
public function getRuntime(): string;

// Metadata
public function setTitle(string $value): self;
public function getTitle(): string;
public function setDescription(string $value): self;
public function getDescription(): string;
public function setHashFamily(?string $hashFamily): self;
public function getHashFamily(): ?string;
public function type(string $type): self;
public function getType(): string;
public function getContent(): array;

// Tags
public function tags(array $tags): self;
public function hasMonitoredTag(): bool;

// Metrics
public function addMetric(Contract $contract): self;

// Type checks
public function isException(): bool;

// Serialization
public function toArray(): array;
```

### Entry Array Structure

The `toArray()` method returns the following structure:

| Key            | Type         | Description                                     |
|----------------|--------------|--------------------------------------------------|
| `uuid`         | string       | Unique identifier (UUID v4)                      |
| `title`        | string       | Auto-generated based on entry type, or custom    |
| `description`  | string       | Auto-generated based on entry type, or custom    |
| `hash_family`  | string\|null | Hash for grouping related entries                |
| `runtime`      | string       | Runtime environment, defaults to `'php'`         |
| `type`         | string       | Entry type constant value                        |
| `content`      | array        | Entry payload data                               |
| `meta`         | array        | Merged metric data from `Metric::toArray()`      |
| `created_at`   | string       | Timestamp in `Y-m-d H:i:s` format               |
| `trace_id`     | string       | OpenTelemetry trace ID (included only when set)  |
| `span_id`      | string       | OpenTelemetry span ID (included only when set)   |

> **Note**: `trace_id` and `span_id` are only present in the array when they have values.
> If an active OpenTelemetry span exists at the time of entry creation, trace context is
> captured automatically.

### Title Auto-Generation

When `title` is not explicitly set, it is generated based on the entry type:

| Type           | Generated Title                                      |
|----------------|------------------------------------------------------|
| `EXCEPTION`    | Exception message from content                       |
| `QUERY`        | SQL query from content                               |
| `QUEUE`        | `Queue Job {name} Failed`                            |
| `HTTP`         | Title from content                                   |
| `COMMAND`      | `Failed command for {command}`                       |
| `NOTIFICATION` | `Failed Notification in {notification}`              |

Titles are truncated to 250 characters.

## ExceptionEntry Class

Specialized entry for exceptions. Located at `Nadi\Data\ExceptionEntry`, extends `Entry`.

```php
public function __construct(\Throwable $exception, string $type, array $content)
```

| Parameter    | Type       | Description                           |
|--------------|------------|---------------------------------------|
| `$exception` | \Throwable | The exception instance                |
| `$type`      | string     | Entry type (typically `Type::EXCEPTION`) |
| `$content`   | array      | Entry payload data                    |

### Behavior Differences from Entry

- `isException()` always returns `true`
- `setHashFamily()` ignores the provided value and generates an MD5 hash from the exception's
  class, file, line, message, and current date — entries for the same exception on the same day
  share a family hash

### Example

```php
use Nadi\Data\ExceptionEntry;
use Nadi\Data\Type;

try {
    // application code
} catch (\Throwable $e) {
    $entry = new ExceptionEntry($e, Type::EXCEPTION, [
        'class' => get_class($e),
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString(),
    ]);
    $entry->setHashFamily(null); // triggers auto-hash generation
}
```

## Type Constants

Entry type constants. Located at `Nadi\Data\Type`.

```php
namespace Nadi\Data;

class Type
{
    public const EXCEPTION    = 'Exception';
    public const QUERY        = 'Query';
    public const QUEUE        = 'Queue';
    public const HTTP         = 'Http';
    public const HTTP_CLIENT  = 'Http Client';
    public const NOTIFICATION = 'Notification';
    public const SCHEDULER    = 'Scheduler';
    public const COMMAND      = 'Command';
    public const GATE         = 'Gate';
    public const LOG          = 'Log';
    public const MAIL         = 'Mail';
}
```

**Type Descriptions:**

| Type           | Description                       |
|----------------|-----------------------------------|
| `EXCEPTION`    | Application exceptions and errors |
| `QUERY`        | Database query telemetry          |
| `QUEUE`        | Queue job events                  |
| `HTTP`         | Incoming HTTP requests            |
| `HTTP_CLIENT`  | Outgoing HTTP requests            |
| `NOTIFICATION` | Notification events               |
| `SCHEDULER`    | Scheduled task events             |
| `COMMAND`      | CLI command events                |
| `GATE`         | Authorization gate checks         |
| `LOG`          | Log entries                       |
| `MAIL`         | Email events                      |

## Arr Utility

Dot-notation array conversion. Located at `Nadi\Support\Arr`.

```php
namespace Nadi\Support;

class Arr
{
    /**
     * Convert dot-notation array to nested array.
     */
    public static function undot(array $array): array;
}
```

**Example:**

```php
$flat = ['a.b.c' => 1, 'a.b.d' => 2];
$nested = Arr::undot($flat);
// ['a' => ['b' => ['c' => 1, 'd' => 2]]]
```

## Next Steps

- [Metrics API](01-metrics.md)
- [Transporter API](03-transporter.md)
- [Basic Usage Guide](../03-usage/01-basic-usage.md)
