# Architecture

This section covers the system design, patterns, and architectural decisions in Nadi PHP SDK.

## Overview

Nadi PHP uses a three-layer architecture with clear separation of concerns:

1. **Metrics Layer** - Collects telemetry data using dot-notation arrays
2. **Sampling Layer** - Controls when data should be captured
3. **Transport Layer** - Handles delivery to backends

## Table of Contents

### [1. Overview](01-overview.md)

High-level system architecture, data flow, and design principles.

### [2. Metrics](02-metrics.md)

Metric collection system, OpenTelemetry semantic conventions, and how to extend with custom metrics.

### [3. Sampling](03-sampling.md)

Sampling strategies, configuration, and custom strategy implementation.

### [4. Transport](04-transport.md)

Transport layer design, HTTP, Log, TCP, and OpenTelemetry transporters.

## Related Documentation

- [Usage Guide](../03-usage/README.md) - Practical implementation guides
- [API Reference](../04-api/README.md) - Class and interface documentation
