<?php

namespace Nadi\Sampling;

class PeakLoadSampling extends BaseSampling
{
    public function shouldSample(): bool
    {
        // Calculate the effective sampling rate based on base rate and load factor
        $effectiveRate = $this->config->getBaseRate() * $this->config->getLoadFactor();

        // Ensure the effective rate does not exceed 1.0 (100%)
        $effectiveRate = min($effectiveRate, 1.0);

        // Determine whether to sample based on the effective rate
        return mt_rand() / mt_getrandmax() < $effectiveRate;
    }
}
