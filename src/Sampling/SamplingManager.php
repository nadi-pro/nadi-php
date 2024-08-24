<?php

namespace Nadi\Sampling;

class SamplingManager
{
    protected $strategy;

    /**
     * SamplingManager constructor.
     */
    public function __construct(Contract $strategy)
    {
        $this->strategy = $strategy;
    }

    /**
     * Determine whether an event should be sampled using the current strategy.
     *
     * @return bool True if the event should be sampled, false otherwise.
     */
    public function shouldSample(): bool
    {
        return $this->strategy->shouldSample();
    }
}
