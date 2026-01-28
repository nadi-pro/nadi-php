# Getting Started

Set up your development environment for contributing to Nadi PHP SDK.

## Requirements

- PHP 8.1 or higher
- Composer
- Docker (optional, for OpenTelemetry testing)

## Installation

Clone the repository and install dependencies:

```bash
git clone https://github.com/nadi-pro/nadi-php.git
cd nadi-php
composer install
```

## Project Structure

```text
nadi-php/
├── src/
│   ├── Concerns/          # Shared traits
│   ├── Data/              # Entry and Type classes
│   ├── Exceptions/        # Exception classes
│   ├── Metric/            # Metric implementations
│   ├── Sampling/          # Sampling strategies
│   ├── Shipper/           # Binary manager
│   ├── Support/           # Utility classes
│   └── Transporter/       # Transport implementations
├── tests/
│   └── Features/          # Feature tests
├── composer.json
└── phpunit.xml
```

## Verify Installation

Run the test suite to verify your setup:

```bash
composer test
```

All tests should pass. OpenTelemetry tests may show connection warnings if Jaeger is not running locally - this is expected.

## Development Dependencies

The SDK includes these development dependencies:

| Package            | Purpose           |
|--------------------|-------------------|
| `laravel/pint`     | Code formatting   |
| `mockery/mockery`  | Test mocking      |
| `phpunit/phpunit`  | Testing framework |

## Next Steps

- [Testing Guide](02-testing.md)
- [Code Style](03-code-style.md)
