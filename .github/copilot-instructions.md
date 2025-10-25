# Nadi PHP SDK - AI Coding Agent Instructions

## Project Overview

Nadi is a PHP SDK for application crash/error monitoring that uses a metric-based architecture with flexible sampling strategies and multiple transport methods. The codebase follows a clean separation of concerns with three main architectural layers:

- **Metrics Layer** (`src/Metric/`): Collects telemetry data using dot-notation arrays converted to associative arrays
- **Sampling Layer** (`src/Sampling/`): Controls when data should be captured using configurable strategies
- **Transport Layer** (`src/Transporter/`): Handles delivery via HTTP API or local logging

## Key Architectural Patterns

### Metric System

All metrics extend `Nadi\Metric\Base` and implement the `Contract` interface. Use dot notation in `metrics()` method - the `Arr::undot()` utility converts to nested arrays automatically:

```php
// In metrics() method - return dot notation
return ['http.status_code' => 200, 'http.method' => 'GET'];
// toArray() converts to: ['http' => ['status_code' => 200, 'method' => 'GET']]
```

The `Metric` class aggregates both built-in system metrics (Browser, Network, OS, Runtime, System) and custom metrics via `add()`.

### Sampling Strategies

All sampling classes implement `Contract->shouldSample(): bool`. The `SamplingManager` provides a wrapper for dynamic strategy switching. Config object holds sampling parameters (rates, intervals, load factors).

### Transport Layer

Transporters implement `Contract` with lifecycle methods: `configure()` → `store()` → `send()`. HTTP transporter requires `key` and `token` credentials, uses Guzzle with custom headers including transporter ID.

## Development Workflows

### Testing
```bash
composer test          # Run PHPUnit tests
composer format         # Run Laravel Pint code formatting
```

### Adding New Metrics

1. Extend `Nadi\Metric\Base`
2. Implement `metrics(): array` returning dot-notation data
3. Use `Nadi\Support\Arr::undot()` for manual conversion if needed
4. Follow existing patterns in `src/Metric/Browser.php` for data transformation

### Adding Sampling Strategies

1. Implement `Nadi\Sampling\Contract`
2. Constructor should accept `Config` object
3. Use `Config` properties: `samplingRate`, `baseRate`, `loadFactor`, `intervalSeconds`

## Project-Specific Conventions

### Namespace Organization

- `Nadi\Metric\*` - All telemetry collection classes
- `Nadi\Sampling\*` - Sampling strategy implementations
- `Nadi\Transporter\*` - Data delivery mechanisms
- `Nadi\Data\*` - Core data structures (Entry, Type definitions)
- `Nadi\Support\*` - Utility classes extending Illuminate components

### Exception Handling

Use static factory methods on exception classes:
```php
TransporterException::throwIfMissingCredentials($key, $token);
```

### Data Flow Pattern

1. Metrics collect data as dot-notation arrays
2. Sampling determines if data should be processed
3. Entry objects wrap metrics with metadata (UUID, type, family hash)
4. Transporters batch and deliver entries

### Laravel Integration Points

- Uses `request()` helper when available for HTTP metrics
- Leverages Illuminate collections and support classes
- Designed for config-based metric registration in Laravel apps

### Key Dependencies

- `hisorange/browser-detect` for browser metrics
- `guzzlehttp/guzzle` for HTTP transport
- `illuminate/support` for utilities and collections
- `ramsey/uuid` for entry identification

## Critical Files

- `src/Metric/Metric.php` - Main aggregation class
- `src/Data/Entry.php` - Core data structure
- `src/Support/Arr.php` - Dot notation conversion utility
- `composer.json` - Wide PHP version support (7.4-8.4)
