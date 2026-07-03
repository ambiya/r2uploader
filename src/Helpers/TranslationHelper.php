<?php
declare(strict_types=1);

use R2Uploader\Service\Translator;

if (!function_exists('__')) {
    /**
     * Translate the given message.
     *
     * @param string $key
     * @param array<string, string> $replace
     * @return string
     */
    function __(string $key, array $replace = []): string
    {
        $translator = Translator::getInstance();
        if ($translator) {
            return $translator->translate($key, $replace);
        }
        
        // Fallback if translator is not initialized yet
        return $key;
    }
}
