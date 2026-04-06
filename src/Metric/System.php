<?php

namespace Nadi\Metric;

class System extends Base
{
    public function metrics(): array
    {
        // @todo need to cover any custom path in order to cover other frameworks.
        $directory = dirname(__FILE__, 3);

        // Laravel
        if (function_exists('base_path')) {
            $directory = base_path();
        }

        return [
            // OTel standard metrics with dot notation (converted by Arr::undot())
            'system.cpu.load_average.1m' => $this->getCpuLoadAverage()[0] ?? 0,
            'system.cpu.load_average.5m' => $this->getCpuLoadAverage()[1] ?? 0,
            'system.cpu.load_average.15m' => $this->getCpuLoadAverage()[2] ?? 0,
            'system.cpu.logical_count' => $this->getCpuCores(),
            'system.memory.usage' => \memory_get_usage(true),
            'system.memory.limit' => $this->getMemoryLimit(),
            'system.memory.peak' => \memory_get_peak_usage(true),
            'system.filesystem.usage' => \disk_total_space($directory) - \disk_free_space($directory),
            'system.filesystem.available' => \disk_free_space($directory),
            'system.filesystem.total' => \disk_total_space($directory),
        ];
    }

    /**
     * Get CPU load average
     */
    protected function getCpuLoadAverage(): array
    {
        if (function_exists('sys_getloadavg')) {
            return \sys_getloadavg();
        }

        if ($this->isWindows() && extension_loaded('com_dotnet')) {
            return $this->getWindowsCpuLoad();
        }

        return [0, 0, 0];
    }

    /**
     * Get number of CPU cores
     */
    protected function getCpuCores(): int
    {
        $cores = 1;

        if (is_file('/proc/cpuinfo')) {
            $result = @shell_exec('nproc');
            $cores = $result !== null ? (int) $result : 1;
        } elseif ($this->isWindows()) {
            $result = @shell_exec('echo %NUMBER_OF_PROCESSORS%');
            $cores = $result !== null ? (int) $result : 1;
        } elseif (stripos(PHP_OS, 'darwin') === 0 || stripos(PHP_OS, 'bsd') !== false) {
            $result = @shell_exec('sysctl -n hw.ncpu');
            $cores = $result !== null ? (int) $result : 1;
        }

        return max($cores, 1);
    }

    /**
     * Get memory limit in bytes
     */
    protected function getMemoryLimit(): int
    {
        $memoryLimit = ini_get('memory_limit');

        if ($memoryLimit === '-1') {
            return PHP_INT_MAX;
        }

        return $this->convertToBytes($memoryLimit);
    }

    /**
     * Convert memory string to bytes
     */
    protected function convertToBytes(string $value): int
    {
        $value = trim($value);
        $unit = strtolower($value[strlen($value) - 1]);
        $number = (int) substr($value, 0, -1);

        switch ($unit) {
            case 'g':
                return $number * 1024 * 1024 * 1024;
            case 'm':
                return $number * 1024 * 1024;
            case 'k':
                return $number * 1024;
            default:
                return (int) $value;
        }
    }

    /**
     * Get Windows CPU load
     */
    protected function getWindowsCpuLoad(): array
    {
        try {
            $wmi = new \COM('winmgmts:{impersonationLevel=impersonate}!\\\\.\\root\\cimv2');
            $query = 'SELECT LoadPercentage FROM Win32_Processor';
            $loadPercentage = $wmi->ExecQuery($query);

            $load = [];
            foreach ($loadPercentage as $processor) {
                $load[] = $processor->LoadPercentage / 100;
            }

            $avgLoad = ! empty($load) ? array_sum($load) / count($load) : 0;

            return [$avgLoad, $avgLoad, $avgLoad];
        } catch (\Throwable $e) {
            return [0, 0, 0];
        }
    }

    /**
     * Check if running on Windows
     */
    protected function isWindows(): bool
    {
        return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    }

    /**
     * Deprecated: Use getCpuLoadAverage() instead
     *
     * @return array
     *
     * @deprecated
     */
    public function getCpu()
    {
        return $this->getCpuLoadAverage();
    }
}
