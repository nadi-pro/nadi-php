<?php

namespace Nadi\Sampling;

class PeakLoadSampling extends BaseSampling
{
    protected ?float $cachedSystemUsage = null;

    protected ?int $cacheTimestamp = null;

    protected int $cacheTtl = 5; // Cache for 5 seconds

    public function shouldSample(): bool
    {
        // Calculate the effective sampling rate based on base rate and load factor
        $effectiveRate = $this->config->getBaseRate() * $this->config->getLoadFactor();

        // Ensure the effective rate does not exceed 1.0 (100%)
        $effectiveRate = min($effectiveRate, 1.0);

        // Get the system usage (CPU and Memory), normalized between 0.0 and 1.0+
        $systemUsage = $this->getSystemUsage();

        // Check if system usage is higher than the effective rate
        if ($systemUsage > $effectiveRate) {
            // Apply randomness: Sample if the random value is less than the effective rate
            return mt_rand() / mt_getrandmax() < $effectiveRate;
        }

        // Do not sample if system usage is not higher than the effective rate
        return false;
    }

    protected function getSystemUsage(): float
    {
        // Use cached value if available and fresh
        if ($this->cachedSystemUsage !== null &&
            $this->cacheTimestamp !== null &&
            (time() - $this->cacheTimestamp) < $this->cacheTtl) {
            return $this->cachedSystemUsage;
        }

        // Get memory usage percentage
        $memoryUsagePercentage = $this->getMemoryUsage();

        // Get CPU usage percentage
        $cpuUsagePercentage = $this->getCpuUsage();

        // Return the maximum of memory and CPU usage
        // This output represents usage based on the maximum value of memory and CPU usage.
        // If either the memory or CPU usage exceeds 100%, the returned value could be greater than 1.0,
        // representing the percentage of overuse (e.g., 1.2 for 120% usage).
        $this->cachedSystemUsage = max($memoryUsagePercentage, $cpuUsagePercentage);
        $this->cacheTimestamp = time();

        return $this->cachedSystemUsage;
    }

    protected function getMemoryUsage(): float
    {
        $currentMemoryUsage = memory_get_usage(true);
        $memoryLimit = ini_get('memory_limit');

        if ($memoryLimit === '-1') {
            return 0.0; // No limit
        }

        $memoryLimitBytes = $this->convertToBytes($memoryLimit);

        return min($currentMemoryUsage / $memoryLimitBytes, 1.0);
    }

    protected function getCpuUsage(): float
    {
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            $cpuUsage = $load[0] / $this->getCpuCores();

            return min($cpuUsage, 1.0);
        }

        if ($this->isWindows() && extension_loaded('com_dotnet')) {
            return $this->getWindowsCpuUsage();
        }

        // Fallback: Try reading from /proc/stat on Linux
        if (is_file('/proc/stat')) {
            return $this->getLinuxCpuUsage();
        }

        return 0.0;
    }

    protected function getCpuCores(): int
    {
        $cores = 1;

        if (is_file('/proc/cpuinfo')) {
            $cores = (int) shell_exec('nproc');
        } elseif ($this->isWindows()) {
            $cores = (int) shell_exec('echo %NUMBER_OF_PROCESSORS%');
        } elseif (stripos(PHP_OS, 'darwin') === 0 || stripos(PHP_OS, 'bsd') !== false) {
            $cores = (int) shell_exec('sysctl -n hw.ncpu');
        }

        return max($cores, 1);
    }

    protected function getWindowsCpuUsage(): float
    {
        try {
            $wmi = new \COM('winmgmts:{impersonationLevel=impersonate}!\\\\.\\root\\cimv2');
            $query = 'SELECT LoadPercentage FROM Win32_Processor';
            $result = $wmi->ExecQuery($query);

            $loads = [];
            foreach ($result as $processor) {
                $loads[] = $processor->LoadPercentage;
            }

            return ! empty($loads) ? (array_sum($loads) / count($loads)) / 100 : 0.0;
        } catch (\Throwable $e) {
            return 0.0;
        }
    }

    protected function getLinuxCpuUsage(): float
    {
        static $prevIdle = null, $prevTotal = null;

        $stat = @file_get_contents('/proc/stat');
        if ($stat === false) {
            return 0.0;
        }

        preg_match('/^cpu\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)/', $stat, $matches);

        if (count($matches) < 6) {
            return 0.0;
        }

        $idle = (int) $matches[4];
        $total = array_sum(array_slice($matches, 1, 5));

        if ($prevIdle === null || $prevTotal === null) {
            $prevIdle = $idle;
            $prevTotal = $total;

            return 0.0;
        }

        $idleDelta = $idle - $prevIdle;
        $totalDelta = $total - $prevTotal;

        $prevIdle = $idle;
        $prevTotal = $total;

        return $totalDelta > 0 ? max(0, 1.0 - ($idleDelta / $totalDelta)) : 0.0;
    }

    protected function convertToBytes(string $value): int
    {
        $value = trim($value);
        $unit = strtolower(substr($value, -1));
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

    protected function isWindows(): bool
    {
        return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    }
}
