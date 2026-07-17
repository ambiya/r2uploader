<?php
declare(strict_types=1);

namespace R2Uploader\Security;

use Exception;

/**
 * URL validator with SSRF (Server-Side Request Forgery) protection.
 *
 * Validates URLs to ensure they use safe protocols and do not resolve
 * to private or reserved IP address ranges.
 */
class UrlValidator
{
    /**
     * Validate a URL for safety against SSRF attacks.
     *
     * @param string $url The URL to validate.
     * @return array{url: string, ip: string, host: string, port: int} The validated URL data.
     * @throws Exception If the URL is invalid, uses a disallowed protocol,
     *                   or resolves to a private/local IP address.
     */
    public static function validate(string $url): array
    {
        $url = trim($url);

        if (empty($url)) {
            throw new Exception('Invalid URL format.');
        }

        $parsed = parse_url($url);

        if ($parsed === false || !isset($parsed['host'])) {
            throw new Exception('Invalid URL format.');
        }

        // Check scheme — must be http or https
        $scheme = strtolower($parsed['scheme'] ?? '');
        if (!in_array($scheme, ['http', 'https'], true)) {
            throw new Exception('URL protocol must be HTTP or HTTPS.');
        }

        $host = $parsed['host'];

        // Validate port
        $port = $parsed['port'] ?? ($scheme === 'https' ? 443 : 80);
        if (!in_array($port, [80, 443], true)) {
            throw new Exception('URL port must be 80 or 443.');
        }

        // Block all direct IPv6 addresses for strict IPv4-only resolution
        if (filter_var(trim($host, '[]'), FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false) {
            throw new Exception('Direct IPv6 address access is not allowed.');
        }

        // Resolve hostname to IP addresses
        $ips = gethostbynamel($host);

        if ($ips === false || empty($ips)) {
            throw new Exception('Host could not be resolved.');
        }

        // Validate each resolved IP against private/reserved ranges
        $safeIp = null;
        foreach ($ips as $ip) {
            $filtered = filter_var(
                $ip,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
            );

            if ($filtered === false) {
                throw new Exception('Access to Private/Local IP is not allowed.');
            }
            $safeIp = $ip;
        }

        return ['url' => $url, 'ip' => $safeIp, 'host' => $host, 'port' => $port];
    }
}
