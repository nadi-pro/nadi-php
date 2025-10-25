<?php

namespace Nadi\Metric;

class Network extends Base
{
    public function metrics(): array
    {
        return [
            // OTel semantic conventions for network/host
            'host.name' => \gethostname(),
            'host.id' => $this->getHostId(),
        ];
    }

    /**
     * Get unique host identifier
     */
    protected function getHostId(): string
    {
        // Try to get machine-id on Linux
        if (is_file('/etc/machine-id')) {
            return trim(file_get_contents('/etc/machine-id'));
        }

        // Try alternate location
        if (is_file('/var/lib/dbus/machine-id')) {
            return trim(file_get_contents('/var/lib/dbus/machine-id'));
        }

        // Fallback to hostname-based hash
        return md5(\gethostname());
    }
}
