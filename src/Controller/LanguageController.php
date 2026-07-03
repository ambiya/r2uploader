<?php
declare(strict_types=1);

namespace R2Uploader\Controller;

use R2Uploader\Http\Request;
use R2Uploader\Http\Response;

class LanguageController extends BaseController
{
    public function switchLang(Request $request): Response
    {
        $lang = $request->query('l');
        if (in_array($lang, ['id', 'en'], true)) {
            $_SESSION['lang'] = $lang;
        }

        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        // Validate referer to prevent open redirect
        $host = $_SERVER['HTTP_HOST'] ?? '';

        $parsedUrl = parse_url($referer);
        if ($referer !== '/') {
             if (isset($parsedUrl['host'])) {
                 $refererHost = $parsedUrl['host'];
                 if (isset($parsedUrl['port'])) {
                     $refererHost .= ':' . $parsedUrl['port'];
                 }
                 if ($refererHost !== $host) {
                     $referer = '/';
                 }
             } elseif (isset($parsedUrl['path']) && str_starts_with($parsedUrl['path'], '//')) {
                 $referer = '/';
             } elseif (isset($parsedUrl['path']) && str_starts_with($parsedUrl['path'], '\\')) {
                 $referer = '/';
             }
        }

        // Also prevent javascript/data schemes
        $scheme = parse_url($referer, PHP_URL_SCHEME);
        if (in_array(strtolower((string)$scheme), ['javascript', 'data', 'vbscript'], true)) {
            $referer = '/';
        }
        return $this->redirect($referer);
    }
}
