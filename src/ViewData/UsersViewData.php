<?php
declare(strict_types=1);

namespace R2Uploader\ViewData;

class UsersViewData extends ViewData
{
    /** @var array<int, array{id: int, username: string, role: string}> */
    public array $users;
    public string $csrfToken;
    public ?string $error;
    public ?string $success;

    public function __construct(array $users, string $csrfToken, ?string $error = null, ?string $success = null)
    {
        $this->users = $users;
        $this->csrfToken = $csrfToken;
        $this->error = $error;
        $this->success = $success;
    }
}
