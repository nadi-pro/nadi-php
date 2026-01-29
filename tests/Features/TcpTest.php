<?php

namespace Nadi\Tests\Features;

use Nadi\Data\Entry;
use Nadi\Data\Type;
use Nadi\Exceptions\TransporterException;
use Nadi\Sampling\Config;
use Nadi\Sampling\FixedRateSampling;
use Nadi\Sampling\SamplingManager;
use Nadi\Tests\TestCase;
use Nadi\Transporter\Tcp;

class TcpTest extends TestCase
{
    // ---------------------------------------------------------------
    // Unit tests — no TCP server required
    // ---------------------------------------------------------------

    public function test_tcp_transporter_missing_host_throws(): void
    {
        $this->expectException(TransporterException::class);
        $this->expectExceptionMessage('Missing TCP host');

        $transporter = new Tcp;
        $transporter->configure(['port' => 15000]);
    }

    public function test_tcp_transporter_missing_port_throws(): void
    {
        $this->expectException(TransporterException::class);
        $this->expectExceptionMessage('Missing TCP port');

        $transporter = new Tcp;
        $transporter->configure(['host' => '127.0.0.1', 'port' => 0]);
    }

    public function test_tcp_transporter_missing_both_throws(): void
    {
        $this->expectException(TransporterException::class);
        $this->expectExceptionMessage('Missing TCP host');

        $transporter = new Tcp;
        $transporter->configure([]);
    }

    public function test_tcp_transporter_default_port(): void
    {
        $this->assertEquals(7430, Tcp::PORT);
    }

    public function test_tcp_transporter_default_values(): void
    {
        $transporter = new Tcp;
        $transporter->configure([
            'host' => '127.0.0.1',
        ]);

        // Verify transporter ID is generated
        $this->assertNotEmpty($transporter->getTransporterId());
        $this->assertEquals(64, strlen($transporter->getTransporterId()));
    }

    public function test_tcp_transporter_store_returns_self(): void
    {
        $transporter = new Tcp;
        $transporter->configure([
            'host' => '127.0.0.1',
            'port' => 15000,
        ]);

        $result = $transporter->store(['type' => 'test']);

        $this->assertInstanceOf(Tcp::class, $result);
    }

    public function test_tcp_transporter_empty_send_returns_true(): void
    {
        $transporter = new Tcp;
        $transporter->configure([
            'host' => '127.0.0.1',
            'port' => 15000,
        ]);

        // send() with no stored data should return true without connecting
        $this->assertTrue($transporter->send());
    }

    public function test_tcp_transporter_send_graceful_failure(): void
    {
        $transporter = new Tcp;
        $transporter->configure([
            'host' => '127.0.0.1',
            'port' => 19999, // unlikely to be listening
            'timeout' => 1,
        ]);

        $transporter->store(['type' => 'test']);

        // send() should return true even on failure (never break the monitored app)
        $this->assertTrue($transporter->send());
    }

    public function test_tcp_transporter_test_graceful_failure(): void
    {
        $transporter = new Tcp;
        $transporter->configure([
            'host' => '127.0.0.1',
            'port' => 19999,
            'timeout' => 1,
        ]);

        // test() should return false on failure
        $this->assertFalse($transporter->test());
    }

    public function test_tcp_transporter_verify_graceful_failure(): void
    {
        $transporter = new Tcp;
        $transporter->configure([
            'host' => '127.0.0.1',
            'port' => 19999,
            'timeout' => 1,
        ]);

        // verify() should return false on failure
        $this->assertFalse($transporter->verify());
    }

    public function test_tcp_transporter_id_not_empty(): void
    {
        $transporter = new Tcp;
        $transporter->configure([
            'host' => '127.0.0.1',
            'port' => 15000,
        ]);

        $this->assertNotEmpty($transporter->getTransporterId());
        $this->assertIsString($transporter->getTransporterId());
    }

    public function test_tcp_transporter_with_mock_socket(): void
    {
        $pair = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);

        if ($pair === false) {
            $this->markTestSkipped('stream_socket_pair not available on this platform.');
        }

        $transporter = new Tcp;
        $transporter->configure([
            'host' => '127.0.0.1',
            'port' => 15000,
        ]);
        $transporter->setSocket($pair[0]);

        $transporter->store(['type' => 'test', 'message' => 'hello']);
        $this->assertTrue($transporter->send());

        // Read from the other end of the pair
        $received = fread($pair[1], 4096);
        $decoded = json_decode(trim($received), true);

        $this->assertEquals('test', $decoded['type']);
        $this->assertEquals('hello', $decoded['message']);

        fclose($pair[1]);
        $transporter->disconnect();
    }

    public function test_tcp_transporter_sends_ndjson(): void
    {
        $pair = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);

        if ($pair === false) {
            $this->markTestSkipped('stream_socket_pair not available on this platform.');
        }

        $transporter = new Tcp;
        $transporter->configure([
            'host' => '127.0.0.1',
            'port' => 15000,
        ]);
        $transporter->setSocket($pair[0]);

        $transporter->store(['type' => 'entry1']);
        $transporter->store(['type' => 'entry2']);
        $transporter->store(['type' => 'entry3']);
        $this->assertTrue($transporter->send());

        $received = fread($pair[1], 8192);
        $lines = array_filter(explode("\n", $received));

        $this->assertCount(3, $lines);

        foreach ($lines as $i => $line) {
            $decoded = json_decode($line, true);
            $this->assertEquals('entry'.($i + 1), $decoded['type']);
        }

        fclose($pair[1]);
        $transporter->disconnect();
    }

    // ---------------------------------------------------------------
    // Integration tests — require TCP server on 127.0.0.1:15000
    // ---------------------------------------------------------------

    protected function skipIfNoTcpServer(): void
    {
        $socket = @stream_socket_client('tcp://127.0.0.1:15000', $errno, $errstr, 1);

        if ($socket === false) {
            $this->markTestSkipped('No TCP server available on 127.0.0.1:15000.');
        }

        fclose($socket);
    }

    public function test_tcp_integration_test(): void
    {
        $this->skipIfNoTcpServer();

        $transporter = new Tcp;
        $transporter->configure([
            'host' => '127.0.0.1',
            'port' => 15000,
        ]);

        $this->assertTrue($transporter->test());
        $transporter->disconnect();
    }

    public function test_tcp_integration_verify(): void
    {
        $this->skipIfNoTcpServer();

        $transporter = new Tcp;
        $transporter->configure([
            'host' => '127.0.0.1',
            'port' => 15000,
        ]);

        $this->assertTrue($transporter->verify());
        $transporter->disconnect();
    }

    public function test_tcp_integration_store_and_send(): void
    {
        $this->skipIfNoTcpServer();

        $transporter = new Tcp;
        $transporter->configure([
            'host' => '127.0.0.1',
            'port' => 15000,
        ]);

        $entry = Entry::make(Type::EXCEPTION, [
            'class' => 'RuntimeException',
            'message' => 'TCP integration test',
            'file' => __FILE__,
            'line' => __LINE__,
        ]);

        $transporter->store($entry->toArray());

        $this->assertTrue($transporter->send());
        $transporter->disconnect();
    }

    public function test_tcp_integration_multiple_entries(): void
    {
        $this->skipIfNoTcpServer();

        $transporter = new Tcp;
        $transporter->configure([
            'host' => '127.0.0.1',
            'port' => 15000,
        ]);

        for ($i = 1; $i <= 5; $i++) {
            $entry = Entry::make(Type::EXCEPTION, [
                'class' => 'RuntimeException',
                'message' => "TCP batch entry {$i}",
                'file' => __FILE__,
                'line' => __LINE__,
            ]);

            $transporter->store($entry->toArray());
        }

        $this->assertTrue($transporter->send());
        $transporter->disconnect();
    }

    public function test_tcp_integration_with_sampling(): void
    {
        $this->skipIfNoTcpServer();

        $config = new Config(samplingRate: 1.0);
        $samplingStrategy = new FixedRateSampling($config);
        $samplingManager = new SamplingManager($samplingStrategy);

        $transporter = new Tcp;
        $transporter->configure([
            'host' => '127.0.0.1',
            'port' => 15000,
        ]);

        if ($samplingManager->shouldSample()) {
            $entry = Entry::make(Type::EXCEPTION, [
                'class' => 'RuntimeException',
                'message' => 'TCP sampled entry',
                'file' => __FILE__,
                'line' => __LINE__,
            ]);

            $transporter->store($entry->toArray());
            $this->assertTrue($transporter->send());
        } else {
            $this->assertTrue(true);
        }

        $transporter->disconnect();
    }
}
