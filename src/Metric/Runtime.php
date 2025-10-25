<?php

namespace Nadi\Metric;

class Runtime extends Base
{
    public function metrics(): array
    {
        return [
            // OTel semantic conventions for process runtime
            'process.runtime.name' => 'php',
            'process.runtime.version' => \phpversion(),
            'process.runtime.description' => 'PHP '.\phpversion(),
            'process.pid' => \getmypid(),
        ];
    }
}
