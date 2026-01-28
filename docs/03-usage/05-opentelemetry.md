# OpenTelemetry Integration

Nadi PHP includes first-class support for OpenTelemetry, enabling integration with standard observability platforms.

## OpenTelemetry Transporter

Configure the OTLP exporter:

```php
use Nadi\Transporter\OpenTelemetry;
use Nadi\Data\Entry;
use Nadi\Data\Type;

$transporter = new OpenTelemetry();
$transporter->configure([
    'endpoint' => 'http://localhost:4318',
    'service_name' => 'my-php-app',
    'service_version' => '1.0.0',
]);

$entry = Entry::make(Type::EXCEPTION, [
    'class' => 'RuntimeException',
    'message' => 'An error occurred',
    'file' => __FILE__,
    'line' => __LINE__,
]);

$transporter->store($entry->toArray());
$transporter->send();
```

## Trace Context Correlation

Entries automatically capture active OpenTelemetry span context:

```php
use Nadi\Data\Entry;
use Nadi\Data\Type;

// Entry captures active OTel span context automatically
$entry = Entry::make(Type::EXCEPTION, [...]);

// Or set manually
$entry->setTraceId('4bf92f3577b34da6a3ce929d0e0e4736');
$entry->setSpanId('00f067aa0ba902b7');

// Trace IDs included in exported data
$data = $entry->toArray();
// Contains: trace_id, span_id
```

## Semantic Conventions

All built-in metrics follow OTel semantic conventions:

### System Metrics

```text
system.cpu.load_average.1m
system.cpu.load_average.5m
system.cpu.load_average.15m
system.cpu.logical_count
system.memory.usage
system.memory.limit
system.memory.peak
system.filesystem.usage
system.filesystem.available
system.filesystem.total
```

### Process Metrics

```text
process.runtime.name
process.runtime.version
process.runtime.description
process.pid
```

### OS Metrics

```text
os.type
os.description
os.name
os.version
host.name
host.arch
```

## Backend Configuration

### Jaeger

```php
$transporter->configure([
    'endpoint' => 'http://jaeger:4318',
    'service_name' => 'my-app',
]);
```

### Grafana Tempo

```php
$transporter->configure([
    'endpoint' => 'http://tempo:4318',
    'service_name' => 'my-app',
]);
```

### Local Development

Run Jaeger for local testing:

```bash
docker run -d --name jaeger \
  -p 4318:4318 \
  -p 16686:16686 \
  jaegertracing/all-in-one:latest
```

Access the UI at `http://localhost:16686`.

## Compatible Platforms

- Jaeger
- Grafana Tempo
- Prometheus (with OTLP receiver)
- Datadog
- New Relic
- Any OTLP-compatible backend

## Next Steps

- [Shipper Binary](06-shipper.md)
- [Architecture Overview](../01-architecture/01-overview.md)
