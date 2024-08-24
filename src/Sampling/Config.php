<?php

namespace Nadi\Sampling;

class Config
{
    public function __construct(
        protected float $samplingRate = 0.1,
        protected float $baseRate = 0.05,
        protected float $loadFactor = 1.0,
        protected float $intervalSeconds = 60
    ) {}

    public function getSamplingRate(): float
    {
        return $this->samplingRate;
    }

    public function getBaseRate(): float
    {
        return $this->baseRate;
    }

    public function getLoadFactor(): float
    {
        return $this->loadFactor;
    }

    public function getIntervalSeconds(): float
    {
        return $this->intervalSeconds;
    }
}
