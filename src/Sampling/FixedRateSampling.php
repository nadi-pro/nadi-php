<?php

namespace Nadi\Sampling;

class FixedRateSampling extends DefaultSampling
{
    public function shouldSample(): bool
    {
        return mt_rand() / mt_getrandmax() < $this->config->getSamplingRate();
    }
}
