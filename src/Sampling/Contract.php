<?php

namespace Nadi\Sampling;

interface Contract
{
    /**
     * Determine whether an event should be sampled.
     *
     * @return bool True if the event should be sampled, false otherwise.
     */
    public function shouldSample(): bool;
}
