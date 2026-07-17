<?php
declare(strict_types=1);

namespace R2Uploader\Service;

use Exception;
use R2Uploader\Security\UrlValidator;

/**
 * Downloads files from remote URLs with SSRF protection.
 *
 * Validates each URL (including redirects) through UrlValidator,
 * enforces redirect limits, and returns download metadata.
 */
class RemoteDownloader
{
    /**
     * Download a stream from a remote URL.
     *
     * Validates the URL against SSRF attacks, follows redirects manually
     * (re-validating each hop), and returns a readable stream.
     *
     * @param string $url The remote URL to download from
     *
     * @return array{stream: resource, contentType: string, originalName: string, size: int|null}
     *
     * @throws Exception If URL validation fails, too many redirects, or stream error
     */
    public function download(string $url): array
    {
        $maxRedirects = 5;
        $currentUrl = $url;
        $redirectCount = 0;
        
        $fp = null;
        $contentType = 'application/octet-stream';
        $size = null;
        
        while (true) {
            $urlData = UrlValidator::validate($currentUrl);
            $parsed = parse_url($currentUrl);
            
            $path = ($parsed['path'] ?? '/') . (isset($parsed['query']) ? '?' . $parsed['query'] : '');
            $scheme = strtolower($parsed['scheme'] ?? 'http');
            $ipUrl = $scheme . "://" . $urlData['ip'] . ":" . $urlData['port'] . $path;
            
            $contextOptions = [
                'http' => [
                    'method' => 'GET',
                    'header' => "Host: {$urlData['host']}\r\nAccept: */*\r\nUser-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)\r\n",
                    'follow_location' => 0, // We handle redirects manually for SSRF protection
                    'timeout' => 120,
                    'ignore_errors' => true,
                ],
            ];
            
            if ($scheme === 'https') {
                $contextOptions['ssl'] = [
                    'peer_name' => $urlData['host'],
                    'verify_peer' => true,
                    'verify_peer_name' => true,
                    'SNI_server_name' => $urlData['host'],
                ];
            }
            
            $context = stream_context_create($contextOptions);
            
            // Suppress warnings as we'll handle failures explicitly
            $fp = @fopen($ipUrl, 'rb', false, $context);
            
            if ($fp === false) {
                throw new Exception("Failed to open connection to target URL.");
            }
            
            $meta = stream_get_meta_data($fp);
            $headers = $meta['wrapper_data'] ?? [];
            
            $httpCode = 200;
            $redirectUrl = null;
            
            foreach ($headers as $header) {
                if (preg_match('#^HTTP/\d\.\d\s+(\d+)#i', $header, $matches)) {
                    $httpCode = (int) $matches[1];
                } elseif (preg_match('/^Location:\s*(.+)$/i', $header, $matches)) {
                    $redirectUrl = trim($matches[1]);
                } elseif (preg_match('/^Content-Type:\s*([^;]+)/i', $header, $matches)) {
                    $contentType = trim($matches[1]);
                } elseif (preg_match('/^Content-Length:\s*(\d+)/i', $header, $matches)) {
                    $size = (int) $matches[1];
                }
            }
            
            if ($httpCode >= 300 && $httpCode < 400) {
                fclose($fp);
                $fp = null;
                
                $redirectCount++;
                if ($redirectCount > $maxRedirects) {
                    throw new Exception('Too many redirects.');
                }
                
                if (empty($redirectUrl)) {
                    throw new Exception('Redirect without Location header.');
                }
                
                // Handle relative redirects
                if (!preg_match('#^https?://#i', $redirectUrl)) {
                    if (str_starts_with($redirectUrl, '/')) {
                        $currentUrl = $scheme . '://' . $urlData['host'] . $redirectUrl;
                    } else {
                        // resolve relative to current path
                        $dir = dirname($parsed['path'] ?? '/');
                        if ($dir === '/' || $dir === '\\') { $dir = ''; }
                        $currentUrl = $scheme . '://' . $urlData['host'] . $dir . '/' . ltrim($redirectUrl, '/');
                    }
                } else {
                    $currentUrl = $redirectUrl;
                }
                
                continue; // Loop again for the redirect
            }
            
            if ($httpCode >= 400) {
                fclose($fp);
                throw new Exception("HTTP Error: {$httpCode}");
            }
            
            // Non-redirect response — done
            break;
        }

        // Parse original filename from URL
        $parsedPath = parse_url($url, PHP_URL_PATH);
        $originalName = 'downloaded_file';
        if (!empty($parsedPath)) {
            $baseName = basename($parsedPath);
            $decoded = urldecode($baseName);
            if (!empty($decoded) && $decoded !== '/' && $decoded !== '.') {
                $originalName = $decoded;
            }
        }

        // Sanitize content type
        if (empty($contentType) || $contentType === 'application/octet-stream') {
            $contentType = 'application/octet-stream';
        }

        return [
            'stream'       => $fp,
            'contentType'  => $contentType,
            'originalName' => $originalName,
            'size'         => $size, // can be null if unknown
        ];
    }
}
