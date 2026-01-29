<?php

namespace Nadi\Transporter;

use Nadi\Concerns\InteractsWithTransporterId;
use Nadi\Exceptions\TransporterException;

class Tcp implements Contract
{
    use InteractsWithTransporterId;

    protected $configurations = [];

    protected $storage = [];

    protected string $host;

    const PORT = 7430;

    protected int $port;

    protected int $timeout = 30;

    protected bool $persistent = true;

    /** @var resource|null */
    protected $socket = null;

    public function configure(array $configurations = []): self
    {
        $this->configurations = $configurations;

        $this->host = $configurations['host'] ?? '';
        $this->port = (int) ($configurations['port'] ?? self::PORT);
        $this->timeout = (int) ($configurations['timeout'] ?? 30);
        $this->persistent = (bool) ($configurations['persistent'] ?? true);

        TransporterException::throwIfMissingTcpCredentials($this->host, $this->port);

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
            $this->ensureConnected();

            foreach ($this->storage as $entry) {
                $line = json_encode($entry)."\n";
                $this->writeToSocket($line);
            }

            $this->storage = [];
        } catch (\Throwable $e) {
            $this->storage = [];

            return true;
        }

        return true;
    }

    public function test()
    {
        try {
            $this->ensureConnected();

            $payload = json_encode([
                'type' => 'nadi.test',
                'transporter_id' => $this->getTransporterId(),
                'timestamp' => date('Y-m-d H:i:s'),
            ])."\n";

            $this->writeToSocket($payload);

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function verify()
    {
        try {
            $this->ensureConnected();

            $payload = json_encode([
                'type' => 'nadi.verify',
                'transporter_id' => $this->getTransporterId(),
                'timestamp' => date('Y-m-d H:i:s'),
            ])."\n";

            $this->writeToSocket($payload);

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Inject a socket resource for testing.
     *
     * @param  resource  $socket
     */
    public function setSocket($socket): self
    {
        $this->socket = $socket;

        return $this;
    }

    protected function ensureConnected(): void
    {
        if (is_resource($this->socket)) {
            return;
        }

        $address = 'tcp://'.$this->host.':'.$this->port;
        $flags = STREAM_CLIENT_CONNECT;

        if ($this->persistent) {
            $flags |= STREAM_CLIENT_PERSISTENT;
        }

        $socket = @stream_socket_client(
            $address,
            $errorCode,
            $errorMessage,
            $this->timeout,
            $flags
        );

        if ($socket === false) {
            throw new \RuntimeException("Failed to connect to {$address}: [{$errorCode}] {$errorMessage}");
        }

        stream_set_timeout($socket, $this->timeout);

        $this->socket = $socket;
    }

    protected function writeToSocket(string $data): void
    {
        $length = strlen($data);
        $written = 0;

        while ($written < $length) {
            $bytes = @fwrite($this->socket, substr($data, $written));

            if ($bytes === false || $bytes === 0) {
                // Reconnect once and retry
                $this->disconnect();
                $this->ensureConnected();

                $bytes = @fwrite($this->socket, substr($data, $written));

                if ($bytes === false || $bytes === 0) {
                    throw new \RuntimeException('Failed to write to TCP socket after reconnect.');
                }
            }

            $written += $bytes;
        }
    }

    public function disconnect(): void
    {
        if (is_resource($this->socket)) {
            @fclose($this->socket);
        }

        $this->socket = null;
    }

    public function __destruct()
    {
        $this->disconnect();
    }
}
