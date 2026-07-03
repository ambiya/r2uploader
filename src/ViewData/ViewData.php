<?php
declare(strict_types=1);

namespace R2Uploader\ViewData;

/**
 * Base class for strongly typed view models.
 * 
 * Provides a method to extract public properties to an array for the template.
 */
abstract class ViewData
{
    /**
     * Extract public properties as an associative array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
