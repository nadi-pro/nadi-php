# API Reference

This section provides reference documentation for core classes and interfaces in Nadi PHP SDK.

## Overview

The SDK is organized into namespaces that reflect the three-layer architecture plus supporting modules.

## Table of Contents

### [1. Metrics](01-metrics.md)

Metric classes and the `Contract` interface for telemetry collection.

### [2. Sampling](02-sampling.md)

Sampling strategy classes, `Config`, and `SamplingManager`.

### [3. Transporter](03-transporter.md)

Transporter classes for HTTP, Log, and OpenTelemetry delivery.

### [4. Data](04-data.md)

`Entry` and `Type` classes for telemetry data structure.

## Namespace Overview

| Namespace          | Purpose                          |
|--------------------|----------------------------------|
| `Nadi\Metric`      | Telemetry collection classes     |
| `Nadi\Sampling`    | Sampling strategy implementations|
| `Nadi\Transporter` | Data delivery mechanisms         |
| `Nadi\Data`        | Core data structures             |
| `Nadi\Support`     | Utility classes                  |
| `Nadi\Shipper`     | Binary management                |
| `Nadi\Exceptions`  | Exception classes                |
| `Nadi\Concerns`    | Shared traits                    |

## Related Documentation

- [Architecture](../01-architecture/README.md) - System design and patterns
- [Usage Guide](../03-usage/README.md) - Practical implementation guides
