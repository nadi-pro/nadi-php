<?php

namespace Nadi\Support;

/**
 * OpenTelemetry Semantic Conventions
 *
 * This class provides constants for OpenTelemetry semantic conventions
 * following the official specifications. These are framework-agnostic
 * and can be used across different implementations.
 *
 * @see https://opentelemetry.io/docs/specs/semconv/
 */
class OpenTelemetrySemanticConventions
{
    // HTTP Semantic Conventions
    public const HTTP_METHOD = 'http.method';

    public const HTTP_URL = 'http.url';

    public const HTTP_SCHEME = 'http.scheme';

    public const HTTP_HOST = 'http.host';

    public const HTTP_TARGET = 'http.target';

    public const HTTP_STATUS_CODE = 'http.status_code';

    public const HTTP_REQUEST_SIZE = 'http.request.size';

    public const HTTP_RESPONSE_SIZE = 'http.response.size';

    public const HTTP_USER_AGENT = 'http.user_agent';

    public const HTTP_ROUTE = 'http.route';

    public const HTTP_CLIENT_IP = 'http.client_ip';

    // Database Semantic Conventions
    public const DB_SYSTEM = 'db.system';

    public const DB_CONNECTION_STRING = 'db.connection_string';

    public const DB_USER = 'db.user';

    public const DB_NAME = 'db.name';

    public const DB_STATEMENT = 'db.statement';

    public const DB_OPERATION = 'db.operation';

    public const DB_SQL_TABLE = 'db.sql.table';

    public const DB_QUERY_DURATION = 'db.query.duration';

    // Exception Semantic Conventions
    public const EXCEPTION_TYPE = 'exception.type';

    public const EXCEPTION_MESSAGE = 'exception.message';

    public const EXCEPTION_STACKTRACE = 'exception.stacktrace';

    public const EXCEPTION_ESCAPED = 'exception.escaped';

    // Error Semantic Conventions
    public const ERROR_TYPE = 'error.type';

    public const ERROR_MESSAGE = 'error.message';

    // Code Semantic Conventions
    public const CODE_FUNCTION = 'code.function';

    public const CODE_NAMESPACE = 'code.namespace';

    public const CODE_FILEPATH = 'code.filepath';

    public const CODE_LINENO = 'code.lineno';

    public const CODE_COLUMN = 'code.column';

    // Service/Resource Semantic Conventions
    public const SERVICE_NAME = 'service.name';

    public const SERVICE_NAMESPACE = 'service.namespace';

    public const SERVICE_INSTANCE_ID = 'service.instance.id';

    public const SERVICE_VERSION = 'service.version';

    public const DEPLOYMENT_ENVIRONMENT = 'deployment.environment';

    // User Semantic Conventions
    public const USER_ID = 'user.id';

    public const USER_NAME = 'user.name';

    public const USER_EMAIL = 'user.email';

    // Session Semantic Conventions
    public const SESSION_ID = 'session.id';

    // Performance Semantic Conventions
    public const MEMORY_USAGE = 'memory.usage';

    public const DURATION = 'duration';

    /**
     * Get basic exception attributes from throwable
     */
    public static function exceptionAttributes(\Throwable $exception): array
    {
        return [
            self::EXCEPTION_TYPE => get_class($exception),
            self::EXCEPTION_MESSAGE => $exception->getMessage(),
            self::EXCEPTION_STACKTRACE => $exception->getTraceAsString(),
            self::CODE_FILEPATH => $exception->getFile(),
            self::CODE_LINENO => $exception->getLine(),
            self::ERROR_TYPE => get_class($exception),
            self::ERROR_MESSAGE => $exception->getMessage(),
        ];
    }

    /**
     * Get performance attributes
     */
    public static function performanceAttributes(float $startTime, ?int $memoryPeak = null): array
    {
        $attributes = [
            self::DURATION => round((microtime(true) - $startTime) * 1000, 2), // in milliseconds
        ];

        if ($memoryPeak !== null) {
            $attributes[self::MEMORY_USAGE] = $memoryPeak;
        }

        return $attributes;
    }
}
