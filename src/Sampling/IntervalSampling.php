<?php

namespace Nadi\Sampling;

class IntervalSampling extends BaseSampling
{
    public function shouldSample(): bool
    {
        return time() % $this->config->getIntervalSeconds() === 0;
    }
}
