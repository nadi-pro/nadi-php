<?php

namespace Nadi\Sampling;

class IntervalSampling extends DefaultSampling
{
    public function shouldSample(): bool
    {
        return time() % $this->config->getIntervalSeconds() === 0;
    }
}
