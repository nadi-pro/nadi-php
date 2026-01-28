# Architecture Overview

Nadi PHP SDK provides application crash/error monitoring with a modular, three-layer architecture
designed for extensibility and OpenTelemetry compatibility.

## Core Layers

### Metrics Layer (`src/Metric/`)

Collects telemetry data using dot-notation arrays that are automatically converted to nested associative arrays.

- All metrics extend `Nadi\Metric\Base` and implement `Contract`
- Built-in metrics: Browser, Network, OperatingSystem, Runtime, System
- Follows OpenTelemetry semantic conventions

### Sampling Layer (`src/Sampling/`)

Controls when data should be captured using configurable strategies.

- All samplers implement `Contract->shouldSample(): bool`
- `SamplingManager` enables dynamic strategy switching
- Five built-in strategies with configurable parameters

### Transport Layer (`src/Transporter/`)

Handles delivery of telemetry data to backends.

- All transporters implement `Contract` with lifecycle: `configure()` → `store()` → `send()`
- Three implementations: HTTP API, Local Logging, OpenTelemetry OTLP

## Data Flow

```text
┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
│  Metrics Layer  │────▶│ Sampling Layer  │────▶│ Transport Layer │
│                 │     │                 │     │                 │
│ Collect data as │     │ Determine if    │     │ Batch and       │
│ dot-notation    │     │ data should be  │     │ deliver entries │
│ arrays          │     │ processed       │     │ to backends     │
└─────────────────┘     └─────────────────┘     └─────────────────┘
         │                                               │
         ▼                                               ▼
┌─────────────────┐                           ┌─────────────────┐
│  Entry Object   │                           │  HTTP / Log /   │
│  UUID, type,    │                           │  OpenTelemetry  │
│  trace context  │                           │                 │
└─────────────────┘                           └─────────────────┘
```

## Key Components

### Entry (`src/Data/Entry.php`)

Core data structure that wraps metrics with metadata:

- UUID for unique identification
- Type classification (Exception, Query, Http, etc.)
- Family hash for grouping related entries
- Trace context (traceId, spanId) for distributed tracing

### Type (`src/Data/Type.php`)

Constants defining entry types:

- Exception, Query, Queue, Http, HttpClient
- Notification, Scheduler, Command, Gate, Log, Mail

### Arr (`src/Support/Arr.php`)

Utility for dot-notation conversion:

- `undot()` converts flat dot-notation to nested arrays
- Enables consistent metric key format

## OpenTelemetry Integration

Nadi PHP has first-class OpenTelemetry support:

- Automatic trace context capture from active OTel spans
- OTLP exporter for standard backends (Jaeger, Grafana Tempo, etc.)
- Semantic conventions for metric naming

## Shipper Binary Manager

The `src/Shipper/` module manages the Nadi Shipper binary:

- `BinaryManager` - Downloads and installs the shipper binary
- `PlatformDetector` - Detects OS and architecture
- `VersionResolver` - Resolves versions from GitHub releases

Supported platforms: Linux (amd64, 386, arm64), macOS (amd64, arm64), Windows (amd64).

## Next Steps

- [Metrics Architecture](02-metrics.md)
- [Sampling Strategies](03-sampling.md)
- [Transport Layer](04-transport.md)
