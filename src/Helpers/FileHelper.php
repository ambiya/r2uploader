<?php
declare(strict_types=1);

namespace R2Uploader\Helpers;

/**
 * File utility helpers for filename sanitization, extension management,
 * size formatting, and MIME type categorization.
 */
class FileHelper
{
    /**
     * Sanitize a filename by removing dangerous characters.
     *
     * - Removes characters: \ / : * ? " < > |
     * - Prevents directory traversal via ".."
     * - Replaces spaces with underscores
     * - Limits total length to 200 characters while preserving the file extension
     *
     * @param string $filename The original filename to sanitize.
     * @return string The sanitized filename.
     */
    public static function sanitizeFilename(string $filename): string
    {
        // Remove dangerous characters: \ / : * ? " < > |
        $filename = preg_replace('/[\\\\\/\:\*\?\"\<\>\|]/', '', $filename);

        // Prevent directory traversal
        while (strpos($filename, '..') !== false) {
            $filename = str_replace('..', '', $filename);
        }

        // Replace spaces with underscores
        $filename = str_replace(' ', '_', $filename);

        // Trim any leading/trailing whitespace or dots
        $filename = trim($filename, " \t\n\r\0\x0B.");

        // Limit to 200 characters while preserving extension
        if (strlen($filename) > 200) {
            $extension = pathinfo($filename, PATHINFO_EXTENSION);
            $name = pathinfo($filename, PATHINFO_FILENAME);

            if ($extension !== '') {
                $maxNameLength = 200 - strlen($extension) - 1; // -1 for the dot
                $name = substr($name, 0, max(1, $maxNameLength));
                $filename = $name . '.' . $extension;
            } else {
                $filename = substr($filename, 0, 200);
            }
        }

        return $filename;
    }

    /**
     * Ensure a filename has an extension by re-appending from the original name if missing.
     *
     * @param string $fileName    The filename to check.
     * @param string $originalName The original filename to extract the extension from.
     * @return string The filename with the extension ensured.
     */
    public static function ensureExtension(string $fileName, string $originalName): string
    {
        $currentExt = pathinfo($fileName, PATHINFO_EXTENSION);

        if (empty($currentExt)) {
            $originalExt = pathinfo($originalName, PATHINFO_EXTENSION);
            if (!empty($originalExt)) {
                $fileName .= '.' . $originalExt;
            }
        }

        return $fileName;
    }

    /**
     * Format a file size in bytes to a human-readable string.
     *
     * @param int $bytes The file size in bytes.
     * @return string Human-readable size (e.g., "1.50 MB").
     */
    public static function formatFileSize(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        }

        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }

        return $bytes . ' bytes';
    }

    /**
     * Determine the MIME category based on file extension.
     *
     * @param string $filename The filename to categorize.
     * @return string One of: 'image', 'video', 'audio', or 'other'.
     */
    public static function getMimeCategory(string $filename): string
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg', 'ico', 'tiff', 'tif', 'avif'];
        $videoExtensions = ['mp4', 'avi', 'mov', 'wmv', 'flv', 'mkv', 'webm', 'm4v', '3gp', 'ogv'];
        $audioExtensions = ['mp3', 'wav', 'ogg', 'flac', 'aac', 'wma', 'm4a', 'opus'];

        if (in_array($extension, $imageExtensions, true)) {
            return 'image';
        }

        if (in_array($extension, $videoExtensions, true)) {
            return 'video';
        }

        if (in_array($extension, $audioExtensions, true)) {
            return 'audio';
        }

        return 'other';
    }
}
