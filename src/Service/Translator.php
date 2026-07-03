<?php
declare(strict_types=1);

namespace R2Uploader\Service;

class Translator
{
    private static ?Translator $instance = null;
    private string $currentLocale;
    private string $fallbackLocale;
    /** @var array<string, array<string, string>> */
    private array $messages = [];
    private string $langPath;

    public function __construct(string $langPath, string $defaultLocale = 'id', string $fallbackLocale = 'en')
    {
        $this->langPath = $langPath;
        $this->currentLocale = $defaultLocale;
        $this->fallbackLocale = $fallbackLocale;
        self::$instance = $this;
    }

    public static function getInstance(): ?Translator
    {
        return self::$instance;
    }

    public function setLocale(string $locale): void
    {
        $this->currentLocale = $locale;
    }

    public function getLocale(): string
    {
        return $this->currentLocale;
    }

    public function translate(string $key, array $replace = []): string
    {
        if (!isset($this->messages[$this->currentLocale])) {
            $this->loadMessages($this->currentLocale);
        }

        $message = $this->messages[$this->currentLocale][$key] ?? null;

        if ($message === null) {
            // Try fallback
            if (!isset($this->messages[$this->fallbackLocale])) {
                $this->loadMessages($this->fallbackLocale);
            }
            $message = $this->messages[$this->fallbackLocale][$key] ?? $key;
        }

        if (empty($replace)) {
            return $message;
        }

        $shouldReplace = [];
        foreach ($replace as $k => $v) {
            $shouldReplace[':' . $k] = $v;
        }

        return strtr($message, $shouldReplace);
    }

    private function loadMessages(string $locale): void
    {
        $file = $this->langPath . '/' . $locale . '.php';
        if (file_exists($file)) {
            $this->messages[$locale] = require $file;
        } else {
            $this->messages[$locale] = [];
        }
    }
}
