<?php

namespace Nadi\Concerns;

trait InteractsWithTransporterId
{
    protected $transporter_id;

    public function getTransporterId(): string
    {
        if (! empty($this->transporter_id)) {
            return $this->transporter_id;
        }

        return $this->transporter_id = bin2hex(random_bytes(32));
    }
}
