<?php

namespace Nadi\Sampling;

class PeakLoadSampling extends DefaultSampling
{
    public function shouldSample(): bool
    {
        if ($this->config->getLoadFactor() > 0.8) {
            return mt_rand() / mt_getrandmax() < 0.2;
        }

        return mt_rand() / mt_getrandmax() < $this->config->getBaseRate();
    }
}
