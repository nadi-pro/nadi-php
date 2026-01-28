# Data API

Reference documentation for data structure classes.

## Entry Class

Core data structure for telemetry entries.

```php
namespace Nadi\Data;

class Entry
{
    /**
     * Create a new entry.
     */
    public static function make(string $type, array $content): self;

    /**
     * Set trace ID for distributed tracing.
     */
    public function setTraceId(string $traceId): self;

    /**
     * Set span ID for distributed tracing.
     */
    public function setSpanId(string $spanId): self;

    /**
     * Convert entry to array.
     */
    public function toArray(): array;
}
```

**Entry Array Structure:**

| Key           | Type        | Description                        |
|---------------|-------------|------------------------------------|
| `uuid`        | string      | Unique identifier (UUID v4)        |
| `type`        | string      | Entry type constant                |
| `content`     | array       | Entry payload                      |
| `family_hash` | string      | Hash for grouping related entries  |
| `trace_id`    | string/null | OpenTelemetry trace ID             |
| `span_id`     | string/null | OpenTelemetry span ID              |

**Trace Context Capture:**

When an active OpenTelemetry span exists, `Entry::make()` automatically captures the trace context.

## Type Constants

Entry type constants.

```php
namespace Nadi\Data;

class Type
{
    public const EXCEPTION = 'exception';
    public const QUERY = 'query';
    public const QUEUE = 'queue';
    public const HTTP = 'http';
    public const HTTP_CLIENT = 'http_client';
    public const NOTIFICATION = 'notification';
    public const SCHEDULER = 'scheduler';
    public const COMMAND = 'command';
    public const GATE = 'gate';
    public const LOG = 'log';
    public const MAIL = 'mail';
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

Dot-notation array conversion.

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
- [Basic Usage Guide](../03-usage/01-basic-usage.md)
