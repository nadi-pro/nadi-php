<?php

namespace Nadi\Sampling;

class PeakLoadSampling extends BaseSampling
{
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
        // Get memory usage percentage
        $memoryUsagePercentage = $this->getMemoryUsage();

        // Get CPU usage percentage
        $cpuUsagePercentage = $this->getCpuUsage();

        // Return the maximum of memory and CPU usage
        // This output represents 75% usage based on the maximum value of memory and CPU usage.
        // If either the memory or CPU usage exceeds 100%, the returned value could be greater than 1.0,
        // representing the percentage of overuse (e.g., 1.2 for 120% usage).
        return max($memoryUsagePercentage, $cpuUsagePercentage);
    }

    protected function getMemoryUsage(): float
    {
        $currentMemoryUsage = memory_get_usage();
        $peakMemoryUsage = memory_get_peak_usage();

        // Calculate memory usage as a percentage of the peak memory usage
        return $currentMemoryUsage / $peakMemoryUsage;
    }

    protected function getCpuUsage(): float
    {
        // Initialize CPU usage variable
        $cpuUsage = null;

        // Use sys_getloadavg() if available and reliable, otherwise use exec
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            $cpuUsage = $load[0] / $this->getCpuCores(); // Normalizing load to the number of CPU cores
        } else {
            // Execute a command to get CPU usage for Linux systems
            exec("top -bn1 | grep 'Cpu(s)' | awk '{print 100 - $8}'", $cpuUsage);
            $cpuUsage = isset($cpuUsage[0]) ? (float) $cpuUsage[0] / 100 : 0;
        }

        // Ensure CPU usage is in the range of 0 to 1.0+ (100%+)
        return min($cpuUsage, 1.0);
    }

    protected function getCpuCores(): int
    {
        $cores = 1; // Default to 1 if unable to detect
        if (is_file('/proc/cpuinfo')) {
            $cores = (int) shell_exec('nproc');
        } elseif (stripos(PHP_OS, 'win') === 0) {
            $cores = (int) shell_exec('echo %NUMBER_OF_PROCESSORS%');
        } elseif (stripos(PHP_OS, 'darwin') === 0 || stripos(PHP_OS, 'bsd') !== false) {
            $cores = (int) shell_exec('sysctl -n hw.ncpu');
        }

        return max($cores, 1);
    }
}
