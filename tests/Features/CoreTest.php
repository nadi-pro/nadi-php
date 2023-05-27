<?php

namespace CleaniqueCoders\Nadi\Tests\Features;

use CleaniqueCoders\Nadi\Exceptions\TransporterException;
use CleaniqueCoders\Nadi\Tests\TestCase;
use CleaniqueCoders\Nadi\Transporter\Http;
use CleaniqueCoders\Nadi\Transporter\Log;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

class CoreTest extends TestCase
{
    /**
     * Test Log Transporter.
     */
    public function test_default_log_transporter(): void
    {
        $transporter = (new Log());
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
        $transporter = (new Log());
        $transporter->configure([
            'path' => dirname(__FILE__, 2).DIRECTORY_SEPARATOR.'logs',
        ]);

        $this->assertTrue($transporter->test());

        $this->assertTrue($transporter->verify());

        unlink($transporter->getFilePath());
        rmdir($transporter->getPath());
    }

    /**
     * Test Http Transporter.
     */
    public function test_http_transporter_exceptions(): void
    {
        $this->expectException(TransporterException::class);
        $this->expectExceptionMessage('Missing API Token');

        $transporter = (new Http());
        $transporter->configure();

        $this->expectException(TransporterException::class);
        $this->expectExceptionMessage('Missing Application Token');

        $transporter->configure([
            'key' => 'unittest-key',
        ]);
    }

    /**
     * Test Http Transporter.
     */
    public function test_http_transporter(): void
    {
        $headers = [
            'Accept' => 'application/vnd.nadi.'.Http::VERSION.'+json',
            'Authorization' => 'Bearer unittest-key',
            'Nadi-Token' => 'unittest-token',
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

        $transporter = new Http();
        $transporter->setClient($client);
        $transporter->configure([
            'key' => 'unittest-key',
            'token' => 'unittest-token',
        ]);

        $this->assertTrue($transporter->test());
        $this->assertTrue($transporter->verify());
        $this->assertTrue($transporter->send([
            'type' => 'Query',
        ])->getStatusCode() == 200);
    }
}
