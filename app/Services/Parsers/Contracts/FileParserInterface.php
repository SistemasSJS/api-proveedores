<?php

namespace App\Services\Parsers\Contracts;

use Illuminate\Http\UploadedFile;

interface FileParserInterface
{
    /**
     * Parse an uploaded file and return array of associative rows
     *
     * @param UploadedFile $file
     * @return array Array of associative arrays representing rows
     * @throws \Exception If parsing fails
     */
    public function parseUploadedFile(UploadedFile $file): array;

    /**
     * Parse a file by path and return array of associative rows
     *
     * @param string $filePath
     * @return array Array of associative arrays representing rows
     * @throws \Exception If parsing fails
     */
    public function parseFilePath(string $filePath): array;

    /**
     * Get supported file extensions for this parser
     *
     * @return array
     */
    public function getSupportedExtensions(): array;

    /**
     * Get supported MIME types for this parser
     *
     * @return array
     */
    public function getSupportedMimeTypes(): array;
}

