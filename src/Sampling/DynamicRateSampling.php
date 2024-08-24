<?php

namespace Nadi\Sampling;

class DynamicRateSampling extends BaseSampling
{
    public function shouldSample(): bool
    {
        return mt_rand() / mt_getrandmax() < (
            $this->config->getBaseRate() * $this->config->getLoadFactor()
        );
    }
}
