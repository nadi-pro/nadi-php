<p align="center">
<a href="https://github.com/nadi-pro/nadi-php/actions"><img src="https://github.com/nadi-pro/nadi-php/actions/workflows/run-tests.yml/badge.svg" alt="Build Status"></a>
</p>

# Nadi PHP Client

Nadi is a simple issue tracker for monitoring your application crashes. This package developed for PHP.

## Installation

```bash
composer require nadi-pro/nadi-php
```

## Adding New Metric

You can add a new metric as you see fit to your application / framework.

Do take note, all metrics will be converted to associative array.

In order to create your own metrics, you need to extends the class `Nadi\Metric\Base` and implement your metrics details in `metrics()` method which always return an array. You may need to define as a dot notation in your metric.

However, Nadi will convert to the associative array.

Following is an example for capture Http request for Laravel framework.

```php
<?php

namespace App\Metric;

use Nadi\Support\Arr;
use Nadi\Metric\Base;
use Illuminate\Support\Str;

class Http extends Base
{
    public function metrics(): array
    {
        $startTime = defined('LARAVEL_START') ? LARAVEL_START : request()->server('REQUEST_TIME_FLOAT');

        return [
            'http.client.duration' => $startTime ? floor((microtime(true) - $startTime) * 1000) : null,
            'http.scheme' => request()->getScheme(),
            'http.route' => request()->getRequestUri(),
            'http.method' => request()->getMethod(),
            'http.status_code' => http_response_code(),
            'http.query' => request()->getQueryString(),
            'http.uri' => str_replace(request()->root(), '', request()->fullUrl()) ?: '/',
            'http.headers' => Arr::undot(collect(request()->headers->all())
                ->map(function ($header) {
                    return $header[0];
                })
                ->reject(function ($header, $key) {
                    return in_array($key, [
                        'authorization', config('nadi.header-key'), 'nadi-key',
                    ]);
                })
                ->toArray()),
        ];
    }
}
```

Once you have declared your metric, you can use in your application:

```php
use App\Metrics\Http;
use Nadi\Metric\Metric;

$metric = new Metric();

$metric->add(new Http());

$metric->toArray();
```

If you are adding from Laravel framework, you can simply just add in `config/nadi.php`:

```php
'metrics' => [
    \App\Metrics\Http::class,
];
```

## Class Diagram

<center>
<img src="nadi-php-uml-diagram.png">
</center>

## Sampling

Following are the sampling strategy provided by default:

1. [Base Sampling](src/Sampling/BaseSampling.php)
2. [Fix Rate Sampling](src/Sampling/FixedRateSampling.php)
3. [Interval Sampling](src/Sampling/IntervalSampling.php)
4. [Peak Load Sampling](src/Sampling/PeakLoadSampling.php)
5. [Dynamic Rate Sampling](src/Sampling/DynamicRateSampling.php)

### Usage

The Sample [Config](src/Sampling/Config.php) can be construct as following:

```php
use Nadi\Sampling\Config;

$config = new Config(
    samplingRate: 0.1,
    baseRate: 0.05,
    loadFactor: 1.0,
    intervalSeconds: 60
);
```

Then based on available sampling strategy, contruct the sampling object:

```php
use Nadi\Sampling\FixedRateSampling;

$samplingStrategy = new FixedRateSampling($config);
```

You can use directly the sampling:

```php
if($samplingStrategy->shouldSample()) {
    // do something
}
```

Or you require [Sampling Manager](src/Sampling/SamplingManager.php):

```php
use Nadi\Sampling\SamplingManager;

$samplingManager = new SamplingManager($samplingStrategy);

if($samplingManager->shouldSample()) {
    // do something
}
```

> Use Sampling Manager if you rely on dynamic use of sampling stategy.

### Create Your Own Sample Strategy

To create your own sampling strategy:

```php

namespace App\Sampling;

use Nadi\Sampling\Contract;
use Nadi\Sampling\Config;

class CustomSampling implements Contract
{
    public function __construct(protected Config $config) {}

    public function shouldSample(): bool
    {
        // do your logic hhere

        return true;
    }
}
```
