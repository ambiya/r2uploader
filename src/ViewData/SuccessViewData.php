<?php
declare(strict_types=1);

namespace R2Uploader\ViewData;

class SuccessViewData extends ViewData
{
    /** @var array<int, array{publicUrl: string, fileSizeMB: string}> */
    public array $successFiles;
    public ?string $type;
    /** @var string[] */
    public array $pruneDeletedKeys;
    public ?int $pruneKeptCount;
    public int $folderMaxFiles;
    public ?string $folder;

    public function __construct(
        array $successFiles,
        ?string $type,
        array $pruneDeletedKeys,
        ?int $pruneKeptCount,
        int $folderMaxFiles,
        ?string $folder = null
    ) {
        $this->successFiles = $successFiles;
        $this->type = $type;
        $this->pruneDeletedKeys = $pruneDeletedKeys;
        $this->pruneKeptCount = $pruneKeptCount;
        $this->folderMaxFiles = $folderMaxFiles;
        $this->folder = $folder;
    }
}
