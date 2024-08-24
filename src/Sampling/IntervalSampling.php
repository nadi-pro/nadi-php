<?php

namespace Nadi\Sampling;

class IntervalSampling extends BaseSampling
{
    protected int $timestamp;

    public function setTimestamp(int $timestamp): self
    {
        $this->timestamp = $timestamp;

        return $this;
    }

    public function getTimestamp(): int
    {
        return $this->timestamp;
    }

    public function hasTimestamp(): bool
    {
        return ! empty($this->timestamp);
    }

    public function shouldSample(): bool
    {
        return (
            $this->hasTimestamp() ? $this->getTimestamp() : time()
        ) % $this->config->getIntervalSeconds() === 0;
    }
}
