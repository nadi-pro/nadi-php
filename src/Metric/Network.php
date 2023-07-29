<?php

namespace Nadi\Metric;

class Network extends Base
{
    public function metrics(): array
    {
        return [
            'net.host.name' => \gethostname(),
        ];
    }
}
