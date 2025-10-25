<?php

namespace Nadi\Transporter;

use Nadi\Concerns\InteractsWithTransporterId;
use Nadi\Support\OpenTelemetrySemanticConventions;
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
                    OpenTelemetrySemanticConventions::DEPLOYMENT_ENVIRONMENT => $configurations['deployment_environment'] ?? 'production',
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

                    // Handle different entry types with semantic conventions
                    $this->addSemanticAttributes($span, $entry);

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
            $span->setAttribute(OpenTelemetrySemanticConventions::SERVICE_NAME, $this->serviceName);
            $span->setAttribute(OpenTelemetrySemanticConventions::SERVICE_VERSION, $this->serviceVersion);
            $span->setStatus(StatusCode::STATUS_OK);
            $span->end();

            return true;
        } catch (\Throwable $e) {
            $this->logger->debug('Verify span failed', ['exception' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Add semantic attributes based on entry type and content
     */
    protected function addSemanticAttributes($span, array $entry): void
    {
        $type = $entry['type'] ?? '';
        $content = $entry['content'] ?? [];

        // Handle exception entries with semantic conventions
        if (in_array($type, ['Exception', 'Error']) && isset($content['exception'])) {
            $exception = $content['exception'];

            // Add exception semantic attributes
            $span->setAttribute(OpenTelemetrySemanticConventions::EXCEPTION_TYPE, $exception['class'] ?? 'Unknown');
            $span->setAttribute(OpenTelemetrySemanticConventions::EXCEPTION_MESSAGE, $exception['message'] ?? '');

            if (isset($exception['file'])) {
                $span->setAttribute(OpenTelemetrySemanticConventions::CODE_FILEPATH, $exception['file']);
            }

            if (isset($exception['line'])) {
                $span->setAttribute(OpenTelemetrySemanticConventions::CODE_LINENO, $exception['line']);
            }

            if (isset($exception['trace'])) {
                $span->setAttribute(OpenTelemetrySemanticConventions::EXCEPTION_STACKTRACE, is_array($exception['trace']) ? json_encode($exception['trace']) : (string) $exception['trace']);
            }
        }

        // Handle HTTP-related entries
        if (isset($content['http']) || $type === 'Request' || $type === 'Http') {
            $http = $content['http'] ?? $content;

            if (isset($http['method'])) {
                $span->setAttribute(OpenTelemetrySemanticConventions::HTTP_METHOD, $http['method']);
            }

            if (isset($http['url'])) {
                $span->setAttribute(OpenTelemetrySemanticConventions::HTTP_URL, $http['url']);
            }

            if (isset($http['status_code'])) {
                $span->setAttribute(OpenTelemetrySemanticConventions::HTTP_STATUS_CODE, $http['status_code']);
            }

            if (isset($http['user_agent'])) {
                $span->setAttribute(OpenTelemetrySemanticConventions::HTTP_USER_AGENT, $http['user_agent']);
            }
        }

        // Handle database-related entries
        if (isset($content['database']) || isset($content['query']) || $type === 'Query') {
            $db = $content['database'] ?? $content;

            if (isset($db['connection_name'])) {
                $span->setAttribute(OpenTelemetrySemanticConventions::DB_SYSTEM, $db['connection_name']);
            }

            if (isset($db['query']) || isset($content['query'])) {
                $query = $db['query'] ?? $content['query'];
                $span->setAttribute(OpenTelemetrySemanticConventions::DB_STATEMENT, $query);
            }

            if (isset($db['duration'])) {
                $span->setAttribute(OpenTelemetrySemanticConventions::DB_QUERY_DURATION, $db['duration']);
            }
        }

        // Handle user context if available
        if (isset($content['user'])) {
            $user = $content['user'];

            if (isset($user['id'])) {
                $span->setAttribute(OpenTelemetrySemanticConventions::USER_ID, (string) $user['id']);
            }

            if (isset($user['name'])) {
                $span->setAttribute(OpenTelemetrySemanticConventions::USER_NAME, $user['name']);
            }

            if (isset($user['email'])) {
                $span->setAttribute(OpenTelemetrySemanticConventions::USER_EMAIL, $user['email']);
            }
        }

        // Handle session context if available
        if (isset($content['session_id'])) {
            $span->setAttribute(OpenTelemetrySemanticConventions::SESSION_ID, $content['session_id']);
        }

        // Add performance metrics if available
        if (isset($content['memory_usage'])) {
            $span->setAttribute(OpenTelemetrySemanticConventions::MEMORY_USAGE, $content['memory_usage']);
        }

        if (isset($content['duration'])) {
            $span->setAttribute(OpenTelemetrySemanticConventions::DURATION, $content['duration']);
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
