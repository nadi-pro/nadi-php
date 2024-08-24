<?php

namespace Nadi\Sampling;

class DefaultSampling implements Contract
{
    public function __construct(protected Config $config) {}

    /**
     * Determine whether an event should be sampled.
     *
     * @return bool True if the event should be sampled, false otherwise.
     */
    public function shouldSample(): bool
    {
        return mt_rand() / mt_getrandmax() < $this->config->getSamplingRate();
    }
}
