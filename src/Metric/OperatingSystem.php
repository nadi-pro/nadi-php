<?php

namespace Nadi\Metric;

class OperatingSystem extends Base
{
    public function metrics(): array
    {
        return [
            // OTel semantic conventions for operating system
            'os.type' => strtolower(PHP_OS_FAMILY),
            'os.description' => \php_uname('s').' '.\php_uname('r'),
            'os.name' => \php_uname('s'),
            'os.version' => \php_uname('r'),
            'host.name' => \php_uname('n'),
            'host.arch' => \php_uname('m'),
        ];
    }
}
