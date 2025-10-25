<?php

namespace Nadi\Transporter;

use OpenTelemetry\SDK\Common\Export\TransportInterface;
use OpenTelemetry\SDK\Common\Future\CancellationInterface;
use OpenTelemetry\SDK\Common\Future\FutureInterface;
use Psr\Log\LoggerInterface;

/**
 * Silent transport wrapper that suppresses error output
 */
class SilentTransportWrapper implements TransportInterface
{
    public function __construct(
        private TransportInterface $transport,
        private LoggerInterface $logger
    ) {}

    public function contentType(): string
    {
        return $this->transport->contentType();
    }

    public function send(string $payload, ?CancellationInterface $cancellation = null): FutureInterface
    {
        // Suppress stderr output
        $errorLevel = error_reporting(0);

        try {
            return $this->transport->send($payload, $cancellation);
        } catch (\Throwable $e) {
            // Log silently instead of outputting to stderr
            $this->logger->debug('OTLP export failed', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);

            // Rethrow to maintain contract
            throw $e;
        } finally {
            // Restore error reporting
            error_reporting($errorLevel);
        }
    }

    public function shutdown(?CancellationInterface $cancellation = null): bool
    {
        return $this->transport->shutdown($cancellation);
    }

    public function forceFlush(?CancellationInterface $cancellation = null): bool
    {
        return $this->transport->forceFlush($cancellation);
    }
}
