<?php


namespace App\Services\FileParser;

use App\Services\Parsers\CsvParser;
use App\Services\Parsers\JsonParser;
use App\Services\Parsers\ExcelParser;
use App\Services\Parsers\Contracts\FileParserInterface;
use Illuminate\Http\UploadedFile;
use InvalidArgumentException;

class FileParserService
{
    /**
     * Array of available parsers
     */
    private array $parsers;

    public function __construct()
    {
        $this->initializeParsers();
    }

    /**
     * Initialize the available parsers
     */
    private function initializeParsers(): void
    {
        $this->parsers = [
            'csv' => new CsvParser(),
            'txt' => new CsvParser(), // TXT files are treated as CSV
            'json' => new JsonParser(),
            'xls' => new ExcelParser(),
            'xlsx' => new ExcelParser(),
        ];
    }

    /**
     * Parse a file and return uniform array of associative rows
     *
     * @param UploadedFile|string $file File to parse (UploadedFile or path)
     * @return array Array of associative arrays representing rows
     * @throws InvalidArgumentException If file format is not supported
     */
    public function parseFile($file): array
    {
        $extension = $this->getFileExtension($file);
        $mimeType = $this->getFileMimeType($file);

        $parser = $this->getParser($extension, $mimeType);

        if ($file instanceof UploadedFile) {
            return $parser->parseUploadedFile($file);
        }

        return $parser->parseFilePath($file);
    }

    /**
     * Get the appropriate parser for the file
     *
     * @param string $extension File extension
     * @param string $mimeType File MIME type
     * @return FileParserInterface
     * @throws InvalidArgumentException If no parser is available
     */
    private function getParser(string $extension, string $mimeType): FileParserInterface
    {
        // First try to match by extension
        if (isset($this->parsers[$extension])) {
            return $this->parsers[$extension];
        }

        // Fallback to MIME type matching
        $parser = $this->getParserByMimeType($mimeType);
        if ($parser) {
            return $parser;
        }

        throw new InvalidArgumentException(
            "Unsupported file format. Extension: {$extension}, MIME: {$mimeType}. " .
                "Supported formats: " . implode(', ', array_keys($this->parsers))
        );
    }

    /**
     * Get parser by MIME type
     *
     * @param string $mimeType
     * @return FileParserInterface|null
     */
    private function getParserByMimeType(string $mimeType): ?FileParserInterface
    {
        $mimeTypeMappings = [
            'text/csv' => $this->parsers['csv'] ?? null,
            'text/plain' => $this->parsers['txt'] ?? null,
            'application/csv' => $this->parsers['csv'] ?? null,
            'application/json' => $this->parsers['json'] ?? null,
            'text/json' => $this->parsers['json'] ?? null,
            'application/vnd.ms-excel' => $this->parsers['xls'] ?? null,
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => $this->parsers['xlsx'] ?? null,
        ];

        return $mimeTypeMappings[$mimeType] ?? null;
    }

    /**
     * Get file extension from file
     *
     * @param UploadedFile|string $file
     * @return string
     */
    private function getFileExtension($file): string
    {
        if ($file instanceof UploadedFile) {
            return strtolower($file->getClientOriginalExtension());
        }

        return strtolower(pathinfo($file, PATHINFO_EXTENSION));
    }

    /**
     * Get file MIME type
     *
     * @param UploadedFile|string $file
     * @return string
     */
    private function getFileMimeType($file): string
    {
        if ($file instanceof UploadedFile) {
            return $file->getMimeType() ?? '';
        }

        // Check if file exists before trying to get MIME type
        if (!file_exists($file)) {
            return '';
        }

        return mime_content_type($file) ?: '';
    }

    /**
     * Get list of supported file extensions
     *
     * @return array
     */
    public function getSupportedExtensions(): array
    {
        return array_keys($this->parsers);
    }

    /**
     * Check if file format is supported
     *
     * @param UploadedFile|string $file
     * @return bool
     */
    public function isSupported($file): bool
    {
        try {
            $extension = $this->getFileExtension($file);
            $mimeType = $this->getFileMimeType($file);
            $this->getParser($extension, $mimeType);
            return true;
        } catch (InvalidArgumentException $e) {
            return false;
        }
    }
}
