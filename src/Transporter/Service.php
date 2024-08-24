<?php

namespace Nadi\Services;

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

    public function handleEvent(array $data)
    {
        if ($this->samplingManager->shouldSample()) {
            $this->transporter->store($data);
        }
    }

    public function sendLogs()
    {
        $this->transporter->send();
    }
}
