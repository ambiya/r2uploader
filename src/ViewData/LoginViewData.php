<?php
declare(strict_types=1);

namespace R2Uploader\ViewData;

class LoginViewData extends ViewData
{
    public string $csrfToken;
    public ?string $error;

    public function __construct(string $csrfToken, ?string $error = null)
    {
        $this->csrfToken = $csrfToken;
        $this->error = $error;
    }
}
