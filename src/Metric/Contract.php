<?php

namespace Nadi\Metric;

interface Contract
{
    public function metrics(): array;

    public function toArray(): array;
}
