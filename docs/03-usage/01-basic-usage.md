# Basic Usage

Core concepts and basic integration patterns for Nadi PHP SDK.

## Installation

Install via Composer:

```bash
composer require nadi-pro/nadi-php
```

## Core Workflow

The basic workflow involves three steps:

1. Collect metrics
2. Create an entry
3. Send via transporter

```php
use Nadi\Metric\Metric;
use Nadi\Data\Entry;
use Nadi\Data\Type;
use Nadi\Transporter\Http;

// 1. Collect metrics
$metric = new Metric();
$metrics = $metric->toArray();

// 2. Create an entry
$entry = Entry::make(Type::EXCEPTION, [
    'class' => 'RuntimeException',
    'message' => 'An error occurred',
    'file' => __FILE__,
    'line' => __LINE__,
]);

// 3. Send via transporter
$transporter = new Http();
$transporter->configure([
    'endpoint' => 'https://api.nadi.pro/api/entries',
    'apiKey' => env('NADI_API_KEY'),
    'appKey' => env('NADI_APP_KEY'),
]);

$transporter->store($entry->toArray());
$transporter->send();
```

## Entry Types

Available entry types in `Nadi\Data\Type`:

| Type                 | Description            |
|----------------------|------------------------|
| `Type::EXCEPTION`    | Application exceptions |
| `Type::QUERY`        | Database queries       |
| `Type::QUEUE`        | Queue job events       |
| `Type::HTTP`         | HTTP requests          |
| `Type::HTTP_CLIENT`  | Outgoing HTTP requests |
| `Type::NOTIFICATION` | Notifications          |
| `Type::SCHEDULER`    | Scheduled tasks        |
| `Type::COMMAND`      | CLI commands           |
| `Type::GATE`         | Authorization gates    |
| `Type::LOG`          | Log entries            |
| `Type::MAIL`         | Email events           |

## Using Sampling

Add sampling to control data volume:

```php
use Nadi\Sampling\Config;
use Nadi\Sampling\FixedRateSampling;

$config = new Config(samplingRate: 0.1);  // 10% sampling
$sampler = new FixedRateSampling($config);

if ($sampler->shouldSample()) {
    $transporter->store($entry->toArray());
    $transporter->send();
}
```

## Laravel Integration

In Laravel, register custom metrics via configuration:

```php
// config/nadi.php
return [
    'metrics' => [
        \App\Metrics\Http::class,
        \App\Metrics\Custom::class,
    ],
];
```

## Next Steps

- [Custom Metrics](02-custom-metrics.md)
- [Transporters](04-transporters.md)
