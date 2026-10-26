<?php

namespace App\Services;

class NetworkService
{
    /**
     * Get all active local IPv4 addresses on the host machine.
     *
     * @return list<string>
     */
    public function getLocalIpv4Addresses(): array
    {
        $ips = [];

        // 1. Primary method on Windows: inspect route/gethostbyname
        $hostname = gethostname();
        if ($hostname !== false) {
            $primaryIp = gethostbyname($hostname);
            if ($this->isValidLocalIpv4($primaryIp)) {
                $ips[] = $primaryIp;
            }
        }

        // 2. Query Windows ipconfig for all IPv4 addresses
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $output = @shell_exec('ipconfig');
            if ($output) {
                // Match "IPv4 Address. . . . . . . . . . . : 192.168.1.15" or Indonesian "Alamat IPv4 . . . . . . . . . . . : 192.168.1.15"
                if (preg_match_all('/(?:IPv4[^:]*|Alamat IPv4[^:]*):\s*([0-9]+\.[0-9]+\.[0-9]+\.[0-9]+)/i', $output, $matches)) {
                    foreach ($matches[1] as $ip) {
                        if ($this->isValidLocalIpv4($ip) && ! in_array($ip, $ips, true)) {
                            $ips[] = $ip;
                        }
                    }
                }
            }
        }

        if (empty($ips)) {
            $ips[] = '127.0.0.1';
        }

        return $ips;
    }

    /**
     * Determine if an IP is a valid private/local IPv4 (excluding loopback 127.0.0.1 and link-local 169.254.x.x).
     */
    private function isValidLocalIpv4(string $ip): bool
    {
        if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return false;
        }

        if ($ip === '127.0.0.1' || str_starts_with($ip, '127.') || str_starts_with($ip, '169.254.')) {
            return false;
        }

        return true;
    }
}
