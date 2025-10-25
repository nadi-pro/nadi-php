<?php

namespace Nadi\Tests\Features;

use Nadi\Support\OpenTelemetrySemanticConventions;
use Nadi\Tests\TestCase;

class OpenTelemetrySemanticConventionsTest extends TestCase
{
    public function test_semantic_conventions_constants_are_defined(): void
    {
        // Test HTTP constants
        $this->assertEquals('http.method', OpenTelemetrySemanticConventions::HTTP_METHOD);
        $this->assertEquals('http.url', OpenTelemetrySemanticConventions::HTTP_URL);
        $this->assertEquals('http.status_code', OpenTelemetrySemanticConventions::HTTP_STATUS_CODE);

        // Test Database constants
        $this->assertEquals('db.system', OpenTelemetrySemanticConventions::DB_SYSTEM);
        $this->assertEquals('db.statement', OpenTelemetrySemanticConventions::DB_STATEMENT);
        $this->assertEquals('db.operation', OpenTelemetrySemanticConventions::DB_OPERATION);

        // Test Exception constants
        $this->assertEquals('exception.type', OpenTelemetrySemanticConventions::EXCEPTION_TYPE);
        $this->assertEquals('exception.message', OpenTelemetrySemanticConventions::EXCEPTION_MESSAGE);
        $this->assertEquals('exception.stacktrace', OpenTelemetrySemanticConventions::EXCEPTION_STACKTRACE);

        // Test Service constants
        $this->assertEquals('service.name', OpenTelemetrySemanticConventions::SERVICE_NAME);
        $this->assertEquals('service.version', OpenTelemetrySemanticConventions::SERVICE_VERSION);
        $this->assertEquals('deployment.environment', OpenTelemetrySemanticConventions::DEPLOYMENT_ENVIRONMENT);

        // Test User constants
        $this->assertEquals('user.id', OpenTelemetrySemanticConventions::USER_ID);
        $this->assertEquals('user.name', OpenTelemetrySemanticConventions::USER_NAME);
        $this->assertEquals('user.email', OpenTelemetrySemanticConventions::USER_EMAIL);

        // Test Performance constants
        $this->assertEquals('memory.usage', OpenTelemetrySemanticConventions::MEMORY_USAGE);
        $this->assertEquals('duration', OpenTelemetrySemanticConventions::DURATION);
    }

    public function test_exception_attributes_method(): void
    {
        $exception = new \RuntimeException('Test exception message', 0);
        $attributes = OpenTelemetrySemanticConventions::exceptionAttributes($exception);

        $this->assertIsArray($attributes);
        $this->assertEquals(\RuntimeException::class, $attributes[OpenTelemetrySemanticConventions::EXCEPTION_TYPE]);
        $this->assertEquals('Test exception message', $attributes[OpenTelemetrySemanticConventions::EXCEPTION_MESSAGE]);
        $this->assertArrayHasKey(OpenTelemetrySemanticConventions::EXCEPTION_STACKTRACE, $attributes);
        $this->assertArrayHasKey(OpenTelemetrySemanticConventions::CODE_FILEPATH, $attributes);
        $this->assertArrayHasKey(OpenTelemetrySemanticConventions::CODE_LINENO, $attributes);
        $this->assertEquals(\RuntimeException::class, $attributes[OpenTelemetrySemanticConventions::ERROR_TYPE]);
        $this->assertEquals('Test exception message', $attributes[OpenTelemetrySemanticConventions::ERROR_MESSAGE]);
    }

    public function test_performance_attributes_method(): void
    {
        $startTime = microtime(true) - 0.1; // 100ms ago
        $memoryPeak = 1024000; // 1MB

        $attributes = OpenTelemetrySemanticConventions::performanceAttributes($startTime, $memoryPeak);

        $this->assertIsArray($attributes);
        $this->assertArrayHasKey(OpenTelemetrySemanticConventions::DURATION, $attributes);
        $this->assertArrayHasKey(OpenTelemetrySemanticConventions::MEMORY_USAGE, $attributes);
        $this->assertGreaterThan(0, $attributes[OpenTelemetrySemanticConventions::DURATION]);
        $this->assertEquals($memoryPeak, $attributes[OpenTelemetrySemanticConventions::MEMORY_USAGE]);
    }

    public function test_performance_attributes_without_memory(): void
    {
        $startTime = microtime(true) - 0.05; // 50ms ago

        $attributes = OpenTelemetrySemanticConventions::performanceAttributes($startTime);

        $this->assertIsArray($attributes);
        $this->assertArrayHasKey(OpenTelemetrySemanticConventions::DURATION, $attributes);
        $this->assertArrayNotHasKey(OpenTelemetrySemanticConventions::MEMORY_USAGE, $attributes);
        $this->assertGreaterThan(0, $attributes[OpenTelemetrySemanticConventions::DURATION]);
    }
}
