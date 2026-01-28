# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Nadi PHP is a PHP SDK for application crash/error monitoring with built-in OpenTelemetry support. It provides metric collection, configurable sampling strategies, and multiple transport methods for telemetry data.

**Package:** `nadi-pro/nadi-php`
**PHP:** 8.1+
**Laravel:** 9.x - 12.x compatible

## Commands

```bash
composer test      # Run PHPUnit test suite
composer format    # Run Laravel Pint code formatter
```

For OpenTelemetry tests with a live OTLP endpoint:

```bash
docker run -d --name jaeger -p 4318:4318 -p 16686:16686 jaegertracing/all-in-one:latest
composer test
docker stop jaeger && docker rm jaeger
```

## Architecture

Three-layer architecture with clear separation of concerns:

### Metrics Layer (`src/Metric/`)

- All metrics extend `Nadi\Metric\Base` and implement `Contract`
- Use dot-notation in `metrics()` method - `Arr::undot()` converts to nested arrays
- Built-in: Browser, Network, OperatingSystem, Runtime, System
- Custom metrics added via `Metric::add()`
- Follows OpenTelemetry semantic conventions (e.g., `system.cpu.load_average.1m`, `process.runtime.name`)

### Sampling Layer (`src/Sampling/`)

- All sampling classes implement `Contract->shouldSample(): bool`
- `SamplingManager` wraps strategies for dynamic switching
- `Config` holds: `samplingRate`, `baseRate`, `loadFactor`, `intervalSeconds`
- Strategies: BaseSampling, FixedRateSampling, IntervalSampling, PeakLoadSampling, DynamicRateSampling

### Transport Layer (`src/Transporter/`)

- Implement `Contract` with lifecycle: `configure()` → `store()` → `send()`
- **Http**: API transport - requires `apiKey` and `appKey` credentials
- **Log**: Local file logging
- **OpenTelemetry**: OTLP exporter for Jaeger, Grafana Tempo, etc.

### Data Layer (`src/Data/`)

- `Entry`: Core data structure with UUID, type, family hash, trace context (traceId, spanId)
- `Type`: Constants for entry types (Exception, Query, Queue, Http, HttpClient, Notification, Scheduler, Command, Gate, Log, Mail)

### Support (`src/Support/`)

- `Arr`: Dot-notation conversion utilities
- `OpenTelemetrySemanticConventions`: OTel naming standards

### Shipper (`src/Shipper/`)

- `BinaryManager`: Auto-downloads Nadi Shipper binary from GitHub releases
- `PlatformDetector`: OS/architecture detection
- Supports: Linux (amd64, 386, arm64), macOS (amd64, arm64), Windows (amd64)

## Data Flow

1. Metrics collect data as dot-notation arrays
2. Sampling determines if data should be processed
3. `Entry` objects wrap metrics with metadata (UUID, type, family hash, trace context)
4. Transporters batch and deliver entries

## Conventions

### Exception Handling

Use static factory methods:

```php
TransporterException::throwIfMissingAppCredentials($apiKey, $appKey);
```

### Namespace Organization

- `Nadi\Metric\*` - Telemetry collection
- `Nadi\Sampling\*` - Sampling strategies
- `Nadi\Transporter\*` - Data delivery
- `Nadi\Data\*` - Core data structures
- `Nadi\Support\*` - Utilities
- `Nadi\Shipper\*` - Binary management

### Adding New Metrics

1. Extend `Nadi\Metric\Base`
2. Implement `metrics(): array` returning dot-notation data
3. Follow OTel semantic conventions for naming
