<?php

namespace App\Services;

use InvalidArgumentException;

class PartnerUrlSafetyService
{
    /**
     * Validate a URL and resolve it to a public IP address suitable for a pinned HTTP request.
     *
     * @return array{scheme:string,host:string,port:int,ip:string}
     */
    public function inspect(string $url): array
    {
        $parts = parse_url($url);

        if (! is_array($parts)) {
            throw new InvalidArgumentException('invalid_url');
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower(trim((string) ($parts['host'] ?? ''), '.'));

        if (! in_array($scheme, ['http', 'https'], true) || $host === '') {
            throw new InvalidArgumentException('unsupported_url');
        }

        if ($this->isBlockedHostname($host)) {
            throw new InvalidArgumentException('blocked_host');
        }

        $port = isset($parts['port'])
            ? (int) $parts['port']
            : ($scheme === 'https' ? 443 : 80);

        if ($port < 1 || $port > 65535) {
            throw new InvalidArgumentException('invalid_port');
        }

        $addresses = $this->resolveHost($host);

        if ($addresses === []) {
            throw new InvalidArgumentException('dns_failed');
        }

        foreach ($addresses as $ip) {
            if (! $this->isPublicIp($ip)) {
                throw new InvalidArgumentException('private_or_reserved_ip');
            }
        }

        $preferredIp = $addresses[0];
        foreach ($addresses as $address) {
            if (filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
                $preferredIp = $address;
                break;
            }
        }

        return [
            'scheme' => $scheme,
            'host' => $host,
            'port' => $port,
            'ip' => $preferredIp,
        ];
    }

    protected function resolveHost(string $host): array
    {
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return [$host];
        }

        $addresses = [];

        if (function_exists('dns_get_record')) {
            $records = @dns_get_record($host, DNS_A | DNS_AAAA);

            if (is_array($records)) {
                foreach ($records as $record) {
                    if (! empty($record['ip'])) {
                        $addresses[] = (string) $record['ip'];
                    }

                    if (! empty($record['ipv6'])) {
                        $addresses[] = (string) $record['ipv6'];
                    }
                }
            }
        }

        if ($addresses === [] && function_exists('gethostbynamel')) {
            $ipv4 = @gethostbynamel($host);
            if (is_array($ipv4)) {
                $addresses = array_merge($addresses, $ipv4);
            }
        }

        return array_values(array_unique(array_filter($addresses)));
    }

    private function isBlockedHostname(string $host): bool
    {
        if (in_array($host, ['localhost', 'localhost.localdomain'], true)) {
            return true;
        }

        return str_ends_with($host, '.localhost')
            || str_ends_with($host, '.local')
            || str_ends_with($host, '.internal');
    }

    private function isPublicIp(string $ip): bool
    {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) !== false;
    }
}
