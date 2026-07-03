<?php
declare(strict_types=1);

namespace R2Uploader\ViewData;

class LayoutViewData extends ViewData
{
    public string $title;
    public string $csrfToken;
    public string $contentHtml;
    /** @var string[] */
    public array $extraJs;

    public function __construct(string $title, string $csrfToken, string $contentHtml, array $extraJs = [])
    {
        $this->title = $title;
        $this->csrfToken = $csrfToken;
        $this->contentHtml = $contentHtml;
        $this->extraJs = $extraJs;
    }
}
