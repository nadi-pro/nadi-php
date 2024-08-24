<?php

namespace Nadi\Transporter;

use Nadi\Sampling\SamplingManager;
use Nadi\Transporter\Contract as Transporter;

class Service
{
    protected Transporter $transporter;

    protected SamplingManager $samplingManager;

    public function __construct(Transporter $transporter, SamplingManager $samplingManager)
    {
        $this->transporter = $transporter;
        $this->samplingManager = $samplingManager;
    }

    public function handle(array $data = [])
    {
        if (!empty($data) && $this->samplingManager->shouldSample()) {
            $this->transporter->store($data);
        }
    }

    public function test()
    {
        return $this->transporter->test();
    }

    public function verify()
    {
        return $this->transporter->verify();
    }

    public function send()
    {
        $this->transporter->send();
    }
}
