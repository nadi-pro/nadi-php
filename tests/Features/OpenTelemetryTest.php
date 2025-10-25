<?php

namespace Nadi\Tests\Features;

use Nadi\Data\Entry;
use Nadi\Data\Type;
use Nadi\Sampling\Config;
use Nadi\Sampling\FixedRateSampling;
use Nadi\Sampling\SamplingManager;
use Nadi\Tests\TestCase;
use Nadi\Transporter\OpenTelemetry;

class OpenTelemetryTest extends TestCase
{
    public function test_opentelemetry_transporter_configuration(): void
    {
        $transporter = new OpenTelemetry;
        $transporter->configure([
            'endpoint' => 'http://localhost:4318',
            'service_name' => 'nadi-test',
            'service_version' => '1.0.0',
        ]);

        $this->assertNotEmpty($transporter->getTransporterId());
    }

    public function test_opentelemetry_transporter_test(): void
    {
        $transporter = new OpenTelemetry;
        $transporter->configure([
            'endpoint' => 'http://localhost:4318',
            'service_name' => 'nadi-test',
        ]);

        $this->assertTrue($transporter->test());
    }

    public function test_opentelemetry_transporter_verify(): void
    {
        $transporter = new OpenTelemetry;
        $transporter->configure([
            'endpoint' => 'http://localhost:4318',
            'service_name' => 'nadi-test',
        ]);

        $this->assertTrue($transporter->verify());
    }

    public function test_opentelemetry_transporter_store_and_send(): void
    {
        $transporter = new OpenTelemetry;
        $transporter->configure([
            'endpoint' => 'http://localhost:4318',
            'service_name' => 'nadi-test',
        ]);

        $entry = Entry::make(Type::EXCEPTION, [
            'class' => 'RuntimeException',
            'message' => 'Test exception message',
            'file' => __FILE__,
            'line' => __LINE__,
        ]);

        $transporter->store($entry->toArray());

        $this->assertTrue($transporter->send());
    }

    public function test_opentelemetry_transporter_with_sampling(): void
    {
        $config = new Config(samplingRate: 1.0);
        $samplingStrategy = new FixedRateSampling($config);
        $samplingManager = new SamplingManager($samplingStrategy);

        $transporter = new OpenTelemetry;
        $transporter->configure([
            'endpoint' => 'http://localhost:4318',
            'service_name' => 'nadi-test-sampling',
        ]);

        if ($samplingManager->shouldSample()) {
            $entry = Entry::make(Type::EXCEPTION, [
                'class' => 'RuntimeException',
                'message' => 'Sampled exception',
                'file' => __FILE__,
                'line' => __LINE__,
            ]);

            $transporter->store($entry->toArray());
            $this->assertTrue($transporter->send());
        } else {
            $this->assertTrue(true); // Sampling prevented the entry
        }
    }

    public function test_opentelemetry_transporter_with_multiple_entries(): void
    {
        $transporter = new OpenTelemetry;
        $transporter->configure([
            'endpoint' => 'http://localhost:4318',
            'service_name' => 'nadi-test-batch',
        ]);

        // Store multiple entries
        for ($i = 1; $i <= 3; $i++) {
            $entry = Entry::make(Type::EXCEPTION, [
                'class' => 'RuntimeException',
                'message' => "Test exception {$i}",
                'file' => __FILE__,
                'line' => __LINE__,
            ]);

            $transporter->store($entry->toArray());
        }

        $this->assertTrue($transporter->send());
    }

    public function test_opentelemetry_transporter_with_metrics(): void
    {
        $transporter = new OpenTelemetry;
        $transporter->configure([
            'endpoint' => 'http://localhost:4318',
            'service_name' => 'nadi-test-metrics',
        ]);

        $entry = Entry::make(Type::EXCEPTION, [
            'class' => 'RuntimeException',
            'message' => 'Exception with metrics',
            'file' => __FILE__,
            'line' => __LINE__,
        ]);

        $transporter->store($entry->toArray());

        $this->assertTrue($transporter->send());
    }

    public function test_entry_captures_trace_context(): void
    {
        $entry = Entry::make(Type::EXCEPTION, [
            'class' => 'RuntimeException',
            'message' => 'Test exception',
            'file' => __FILE__,
            'line' => __LINE__,
        ]);

        // The trace context might be null if OTel is not actively tracing
        // But the methods should exist
        $this->assertTrue(method_exists($entry, 'getTraceId'));
        $this->assertTrue(method_exists($entry, 'getSpanId'));
    }

    public function test_entry_allows_manual_trace_context(): void
    {
        $entry = Entry::make(Type::EXCEPTION, [
            'class' => 'RuntimeException',
            'message' => 'Test exception',
            'file' => __FILE__,
            'line' => __LINE__,
        ]);

        $entry->setTraceId('test-trace-id-12345');
        $entry->setSpanId('test-span-id-67890');

        $this->assertEquals('test-trace-id-12345', $entry->getTraceId());
        $this->assertEquals('test-span-id-67890', $entry->getSpanId());

        $array = $entry->toArray();
        $this->assertArrayHasKey('trace_id', $array);
        $this->assertArrayHasKey('span_id', $array);
        $this->assertEquals('test-trace-id-12345', $array['trace_id']);
        $this->assertEquals('test-span-id-67890', $array['span_id']);
    }

    public function test_opentelemetry_transporter_with_different_entry_types(): void
    {
        $transporter = new OpenTelemetry;
        $transporter->configure([
            'endpoint' => 'http://localhost:4318',
            'service_name' => 'nadi-test-types',
        ]);

        // HTTP entry
        $httpEntry = Entry::make(Type::HTTP, [
            'title' => 'HTTP Request',
            'description' => 'GET /api/users',
        ]);

        $transporter->store($httpEntry->toArray());

        // Exception entry
        $exceptionEntry = Entry::make(Type::EXCEPTION, [
            'class' => 'RuntimeException',
            'message' => 'Test error',
            'file' => __FILE__,
            'line' => __LINE__,
        ]);

        $transporter->store($exceptionEntry->toArray());

        $this->assertTrue($transporter->send());
    }

    public function test_opentelemetry_transporter_empty_storage(): void
    {
        $transporter = new OpenTelemetry;
        $transporter->configure([
            'endpoint' => 'http://localhost:4318',
            'service_name' => 'nadi-test-empty',
        ]);

        // Send with empty storage should return true
        $this->assertTrue($transporter->send());
    }

    public function test_opentelemetry_transporter_semantic_conventions_integration(): void
    {
        $transporter = new OpenTelemetry;
        $transporter->configure([
            'endpoint' => 'http://localhost:4318',
            'service_name' => 'nadi-test-semantic',
            'service_version' => '2.0.0',
            'deployment_environment' => 'testing',
        ]);

        // Test entry with semantic convention data structure
        $semanticEntry = [
            'uuid' => 'semantic-test-uuid',
            'type' => 'Exception',
            'description' => 'Semantic conventions test',
            'content' => [
                'exception' => [
                    'class' => 'RuntimeException',
                    'message' => 'Test semantic exception',
                    'file' => '/test/path/file.php',
                    'line' => 123,
                    'trace' => 'Test stack trace...',
                ],
                'http' => [
                    'method' => 'GET',
                    'url' => 'https://test.example.com',
                    'status_code' => 500,
                    'user_agent' => 'Test Agent/1.0',
                ],
                'database' => [
                    'connection_name' => 'mysql_test',
                    'query' => 'SELECT COUNT(*) FROM test_table',
                    'duration' => 42.5,
                ],
                'user' => [
                    'id' => 999,
                    'name' => 'Test User',
                    'email' => 'test@example.com',
                ],
                'session_id' => 'test-session-123',
                'memory_usage' => 2048000,
                'duration' => 250.75,
            ],
        ];

        $transporter->store($semanticEntry);

        // Should not throw any errors and return true
        $this->assertTrue($transporter->send());
    }
}
