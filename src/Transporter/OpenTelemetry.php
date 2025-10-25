<?php

namespace Nadi\Transporter;

use Nadi\Concerns\InteractsWithTransporterId;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\Contrib\Otlp\SpanExporter;
use OpenTelemetry\SDK\Common\Attribute\Attributes;
use OpenTelemetry\SDK\Common\Export\Http\PsrTransportFactory;
use OpenTelemetry\SDK\Resource\ResourceInfo;
use OpenTelemetry\SDK\Resource\ResourceInfoFactory;
use OpenTelemetry\SDK\Trace\SpanProcessor\SimpleSpanProcessor;
use OpenTelemetry\SDK\Trace\TracerProvider;
use OpenTelemetry\SemConv\ResourceAttributes;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class OpenTelemetry implements Contract
{
    use InteractsWithTransporterId;

    protected TracerProvider $tracerProvider;

    protected array $configurations = [];

    protected array $storage = [];

    protected string $endpoint;

    protected string $serviceName;

    protected string $serviceVersion;

    protected bool $suppressExportErrors = true;

    protected ?LoggerInterface $logger = null;

    public function configure(array $configurations = []): self
    {
        $this->configurations = $configurations;
        $this->endpoint = $configurations['endpoint'] ?? 'http://localhost:4318';
        $this->serviceName = $configurations['service_name'] ?? 'nadi-php';
        $this->serviceVersion = $configurations['service_version'] ?? '1.0.0';
        $this->suppressExportErrors = $configurations['suppress_errors'] ?? true;
        $this->logger = $configurations['logger'] ?? new NullLogger;

        // Create resource with service information
        $resource = ResourceInfoFactory::defaultResource()->merge(
            ResourceInfo::create(
                Attributes::create([
                    ResourceAttributes::SERVICE_NAME => $this->serviceName,
                    ResourceAttributes::SERVICE_VERSION => $this->serviceVersion,
                    'nadi.transporter_id' => $this->getTransporterId(),
                ])
            )
        );

        // Create transport for OTLP exporter
        $transport = (new PsrTransportFactory)->create(
            $this->endpoint.'/v1/traces',
            'application/x-protobuf'
        );

        // Suppress OpenTelemetry internal error logging if configured
        if ($this->suppressExportErrors) {
            putenv('OTEL_LOG_LEVEL=none');
        }

        // Wrap transport to suppress error output if requested
        if ($this->suppressExportErrors) {
            $transport = new SilentTransportWrapper($transport, $this->logger);
        }

        // Create OTLP exporter
        $exporter = new SpanExporter($transport);

        // Use SimpleSpanProcessor for immediate export
        $processor = new SimpleSpanProcessor($exporter);

        // Create tracer provider
        $this->tracerProvider = new TracerProvider(
            $processor,
            null,
            $resource
        );

        return $this;
    }

    public function store(array $data): self
    {
        $this->storage[] = $data;

        return $this;
    }

    public function send()
    {
        if (empty($this->storage)) {
            return true;
        }

        try {
            $tracer = $this->tracerProvider->getTracer('nadi-php', $this->serviceVersion);

            foreach ($this->storage as $entry) {
                $spanName = $entry['title'] ?? $entry['type'] ?? 'Nadi Event';

                // Determine span kind based on entry type
                $spanKind = $this->determineSpanKind($entry['type'] ?? '');

                $span = $tracer->spanBuilder($spanName)
                    ->setSpanKind($spanKind)
                    ->startSpan();

                try {
                    // Add entry metadata
                    $span->setAttribute('nadi.uuid', $entry['uuid'] ?? '');
                    $span->setAttribute('nadi.type', $entry['type'] ?? '');
                    $span->setAttribute('nadi.hash_family', $entry['hash_family'] ?? '');

                    // Add description if available
                    if (isset($entry['description'])) {
                        $span->setAttribute('nadi.description', $entry['description']);
                    }

                    // Add metrics as span attributes (flattened)
                    if (isset($entry['meta'])) {
                        foreach ($this->flattenArray($entry['meta']) as $key => $value) {
                            if (is_scalar($value) || is_null($value)) {
                                $span->setAttribute($key, $value);
                            }
                        }
                    }

                    // Add content if available
                    if (isset($entry['content'])) {
                        foreach ($this->flattenArray($entry['content'], 'content') as $key => $value) {
                            if (is_scalar($value) || is_null($value)) {
                                $span->setAttribute($key, $value);
                            }
                        }
                    }

                    // Set span status based on entry type
                    if (isset($entry['type']) && in_array($entry['type'], ['Exception', 'Error'])) {
                        $span->setStatus(StatusCode::STATUS_ERROR, $entry['description'] ?? 'Error occurred');
                    } else {
                        $span->setStatus(StatusCode::STATUS_OK);
                    }
                } catch (\Throwable $e) {
                    $span->recordException($e);
                    $span->setStatus(StatusCode::STATUS_ERROR, $e->getMessage());
                    $this->logger->error('Failed to set span attributes', ['exception' => $e->getMessage()]);
                } finally {
                    $span->end();
                }
            }

            $this->storage = [];

            return true;
        } catch (\Throwable $e) {
            $this->logger->error('Failed to send telemetry data', ['exception' => $e->getMessage()]);

            // Clear storage even on failure to prevent memory buildup
            $this->storage = [];

            // Return true to not break the application flow
            return true;
        }
    }

    public function test()
    {
        try {
            $tracer = $this->tracerProvider->getTracer('nadi-php', $this->serviceVersion);
            $span = $tracer->spanBuilder('nadi.test')
                ->setSpanKind(SpanKind::KIND_INTERNAL)
                ->startSpan();

            $span->setAttribute('test', true);
            $span->setAttribute('transporter_id', $this->getTransporterId());
            $span->setStatus(StatusCode::STATUS_OK);
            $span->end();

            return true;
        } catch (\Throwable $e) {
            $this->logger->debug('Test span failed', ['exception' => $e->getMessage()]);

            return false;
        }
    }

    public function verify()
    {
        try {
            $tracer = $this->tracerProvider->getTracer('nadi-php', $this->serviceVersion);
            $span = $tracer->spanBuilder('nadi.verify')
                ->setSpanKind(SpanKind::KIND_INTERNAL)
                ->startSpan();

            $span->setAttribute('verify', true);
            $span->setAttribute('transporter_id', $this->getTransporterId());
            $span->setAttribute('service.name', $this->serviceName);
            $span->setAttribute('service.version', $this->serviceVersion);
            $span->setStatus(StatusCode::STATUS_OK);
            $span->end();

            return true;
        } catch (\Throwable $e) {
            $this->logger->debug('Verify span failed', ['exception' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Determine span kind based on entry type
     */
    protected function determineSpanKind(string $type): int
    {
        return match (strtolower($type)) {
            'http', 'request' => SpanKind::KIND_SERVER,
            'client', 'external' => SpanKind::KIND_CLIENT,
            default => SpanKind::KIND_INTERNAL,
        };
    }

    /**
     * Flatten nested array into dot notation
     */
    protected function flattenArray(array $array, string $prefix = ''): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            $newKey = $prefix ? $prefix.'.'.$key : $key;

            if (is_array($value)) {
                $result = array_merge($result, $this->flattenArray($value, $newKey));
            } else {
                $result[$newKey] = $value;
            }
        }

        return $result;
    }
}
