<?php

namespace Nadi\Tests\Features;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Nadi\Exceptions\TransporterException;
use Nadi\Sampling\Config;
use Nadi\Sampling\DynamicRateSampling;
use Nadi\Sampling\FixedRateSampling;
use Nadi\Sampling\IntervalSampling;
use Nadi\Sampling\PeakLoadSampling;
use Nadi\Sampling\SamplingManager;
use Nadi\Tests\TestCase;
use Nadi\Transporter\Http;
use Nadi\Transporter\Log;

class CoreTest extends TestCase
{
    /**
     * Test Log Transporter.
     */
    public function test_default_log_transporter(): void
    {
        $transporter = (new Log);
        $transporter->configure();

        $this->assertTrue($transporter->test());

        $this->assertTrue($transporter->verify());

        unlink($transporter->getFilePath());
    }

    /**
     * Test Custom Path of Log Transporter.
     */
    public function test_custom_log_transporter(): void
    {
        $transporter = (new Log);
        $path = dirname(__FILE__, 2).DIRECTORY_SEPARATOR.'logs';
        $gitignore_path = $path.DIRECTORY_SEPARATOR.'.gitignore';
        $transporter->configure([
            'path' => $path,
        ]);

        $this->assertDirectoryExists($path);
        $this->assertFileExists($gitignore_path);
        $this->assertEquals('*'.PHP_EOL.'!.gitignore', file_get_contents($gitignore_path));

        $this->assertTrue($transporter->test());

        $this->assertTrue($transporter->verify());
        unlink($gitignore_path);
        unlink($transporter->getFilePath());
        rmdir($transporter->getPath());
    }

    /**
     * Test Http Transporter.
     */
    public function test_http_transporter_exceptions(): void
    {
        $this->expectException(TransporterException::class);
        $this->expectExceptionMessage('Missing API Key (NADI_API_KEY)');

        $transporter = new Http;
        $transporter->configure();
    }

    /**
     * Test Http Transporter missing App Secret.
     */
    public function test_http_transporter_missing_app_key(): void
    {
        $this->expectException(TransporterException::class);
        $this->expectExceptionMessage('Missing App Key (NADI_APP_KEY)');

        $transporter = new Http;
        $transporter->configure([
            'api_key' => 'unittest-api-key',
        ]);
    }

    /**
     * Test Http Transporter.
     */
    public function test_http_transporter(): void
    {
        $headers = [
            'Accept' => 'application/vnd.nadi.'.Http::VERSION.'+json',
            'Nadi-App-Id' => 'unittest-app-id',
            'Nadi-App-Secret' => 'unittest-app-secret',
            'Nadi-Transporter-Id' => '07f44616ac3c5812d914d8ea537b0df70abd69205cc278019547e27bddabf3e1',
            'Content-Type' => 'application/json',
        ];

        $mock = new MockHandler([
            new Response(200, $headers),
            new Response(200, $headers),
            new Response(200, $headers),
        ]);

        $handler = HandlerStack::create($mock);
        $client = new Client([
            'handler' => $handler,
            'headers' => $headers,
        ]);

        $transporter = new Http;
        $transporter->configure([
            'api_key' => 'unittest-api-key',
            'app_key' => 'unittest-app-key',
        ]);
        $transporter->setClient($client);

        $this->assertTrue($transporter->test());
        $this->assertTrue($transporter->verify());
        $transporter->store([
            'type' => 'Query',
        ]);
        $this->assertTrue($transporter->send()->getStatusCode() == 200);
    }

    /**
     * Test Fixed Rate Sampling.
     */
    public function test_fixed_rate_sampling(): void
    {
        $config = new Config(samplingRate: 1.0); // 100% sampling rate
        $samplingStrategy = new FixedRateSampling($config);
        $samplingManager = new SamplingManager($samplingStrategy);

        $this->assertTrue($samplingManager->shouldSample(), 'Sampling should occur at 100% rate.');

        $config = new Config(samplingRate: 0.0); // 0% sampling rate
        $samplingStrategy = new FixedRateSampling($config);
        $samplingManager = new SamplingManager($samplingStrategy);

        $this->assertFalse($samplingManager->shouldSample(), 'Sampling should not occur at 0% rate.');
    }

    /**
     * Test Dynamic Rate Sampling.
     */
    public function test_dynamic_rate_sampling(): void
    {
        // Test with 100% effective rate (baseRate * loadFactor)
        $config = new Config(baseRate: 1.0, loadFactor: 1.0);
        $samplingStrategy = new DynamicRateSampling($config);
        $samplingManager = new SamplingManager($samplingStrategy);

        $this->assertTrue($samplingManager->shouldSample(), 'Sampling should occur at 100% dynamic rate.');

        // Test with 0% effective rate
        $config = new Config(baseRate: 0.0, loadFactor: 1.0);
        $samplingStrategy = new DynamicRateSampling($config);
        $samplingManager = new SamplingManager($samplingStrategy);

        $this->assertFalse($samplingManager->shouldSample(), 'Sampling should not occur at 0% dynamic rate.');
    }

    /**
     * Test Interval Sampling.
     */
    public function test_interval_sampling(): void
    {
        // Test at a time that aligns with the interval
        $config = new Config(intervalSeconds: 60); // Sample every 60 seconds
        $samplingStrategy = new IntervalSampling($config);
        $samplingManager = new SamplingManager($samplingStrategy);

        // // Mock time to align with the interval
        $samplingStrategy->setTimestamp(1627857600);
        $this->assertTrue($samplingStrategy->shouldSample(), "Sampling should occur at the interval of {$config->getIntervalSeconds()} seconds.");

        // Test at a time that does not align with the interval
        $mockTime = 1627857601; // 60 seconds after the interval
        $samplingStrategy = new IntervalSampling($config);
        $samplingManager = new SamplingManager($samplingStrategy);
        $samplingStrategy->setTimestamp($mockTime);

        $this->assertFalse($samplingManager->shouldSample(), 'Sampling should not occur outside of the interval.');
    }

    /**
     * Test Peak Load Sampling.
     */
    public function test_peak_load_sampling(): void
    {
        // Test with high load factor resulting in high sampling rate
        // this is not possible for testing as it's require CPU & Memory Usage
        // instead of normal calculation.

        // Test with low load factor resulting in low sampling rate
        $config = new Config(baseRate: 0.05, loadFactor: 0.1); // Effective rate = 0.005
        $samplingStrategy = new PeakLoadSampling($config);
        $samplingManager = new SamplingManager($samplingStrategy);

        $this->assertFalse($samplingManager->shouldSample(), 'Sampling should not occur at low load.');

    }

    /**
     * Test Log Transporter with Sampling.
     */
    public function test_log_transporter_with_sampling(): void
    {
        $config = new Config(samplingRate: 1.0); // 100% sampling rate
        $samplingStrategy = new FixedRateSampling($config);
        $samplingManager = new SamplingManager($samplingStrategy);

        $transporter = new Log;
        $transporter->configure();

        $this->assertTrue($transporter->test());

        $this->assertTrue($transporter->verify());

        if ($samplingManager->shouldSample()) {
            $transporter->store(['message' => 'Sampled log message']);
            $this->assertTrue(file_exists($transporter->getFilePath()));
        }

        unlink($transporter->getFilePath());
    }

    /**
     * Test Http Transporter with Sampling.
     */
    public function test_http_transporter_with_sampling(): void
    {
        $config = new Config(samplingRate: 1.0); // 100% sampling rate
        $samplingStrategy = new FixedRateSampling($config);
        $samplingManager = new SamplingManager($samplingStrategy);

        $headers = [
            'Accept' => 'application/vnd.nadi.'.Http::VERSION.'+json',
            'Nadi-App-Id' => 'unittest-app-id',
            'Nadi-App-Secret' => 'unittest-app-secret',
            'Nadi-Transporter-Id' => '07f44616ac3c5812d914d8ea537b0df70abd69205cc278019547e27bddabf3e1',
            'Content-Type' => 'application/json',
        ];

        $mock = new MockHandler([
            new Response(200, $headers),
            new Response(200, $headers),
            new Response(200, $headers),
        ]);

        $handler = HandlerStack::create($mock);
        $client = new Client([
            'handler' => $handler,
            'headers' => $headers,
        ]);

        $transporter = new Http;
        $transporter->configure([
            'api_key' => 'unittest-api-key',
            'app_key' => 'unittest-app-key',
        ]);
        $transporter->setClient($client);

        if ($samplingManager->shouldSample()) {
            $transporter->store(['type' => 'Query']);
            $response = $transporter->send();
            $this->assertEquals(200, $response->getStatusCode());
        }
    }
}
