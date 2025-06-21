<?php

namespace App\Services\Parsers;

use App\Services\Parsers\Contracts\FileParserInterface;
use Illuminate\Http\UploadedFile;
use InvalidArgumentException;
use Exception;

class JsonParser implements FileParserInterface
{
    public function parseUploadedFile(UploadedFile $file): array
    {
        $this->validateFile($file);
        
        // Read file content
        $content = file_get_contents($file->getRealPath());
        
        return $this->parseJsonContent($content);
    }

    public function parseFilePath(string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw new InvalidArgumentException("File not found: {$filePath}");
        }

        if (!is_readable($filePath)) {
            throw new InvalidArgumentException("File is not readable: {$filePath}");
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new Exception("Failed to read file: {$filePath}");
        }

        return $this->parseJsonContent($content);
    }

    public function getSupportedExtensions(): array
    {
        return ['json'];
    }

    public function getSupportedMimeTypes(): array
    {
        return [
            'application/json',
            'text/json',
            'application/x-json'
        ];
    }

    /**
     * Parse JSON content and normalize to array of associative arrays
     *
     * @param string $content
     * @return array
     * @throws Exception
     */
    private function parseJsonContent(string $content): array
    {
        // Remove BOM if present
        $content = str_replace("\xEF\xBB\xBF", '', $content);
        
        // Trim whitespace
        $content = trim($content);
        
        if (empty($content)) {
            return [];
        }

        $data = json_decode($content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON: ' . json_last_error_msg());
        }

        return $this->normalizeJsonData($data);
    }

    /**
     * Normalize JSON data to array of associative arrays (like CSV output)
     *
     * @param mixed $data
     * @return array
     * @throws Exception
     */
    private function normalizeJsonData($data): array
    {
        if (empty($data)) {
            return [];
        }

        // If data is already an array of associative arrays, return as-is
        if (is_array($data) && $this->isArrayOfAssociativeArrays($data)) {
            return $data;
        }

        // If data has a 'data' or 'rows' key containing the actual records, check this first
        if (is_array($data)) {
            foreach (['data', 'rows', 'records', 'items'] as $key) {
                if (isset($data[$key]) && is_array($data[$key])) {
                    return $this->normalizeJsonData($data[$key]);
                }
            }
        }

        // If data is a single associative array, wrap it in an array
        if (is_array($data) && $this->isAssociativeArray($data)) {
            return [$data];
        }

        // If data is an array of mixed types, try to convert to associative arrays
        if (is_array($data)) {
            $result = [];
            foreach ($data as $index => $item) {
                if (is_array($item)) {
                    $result[] = $item;
                } elseif (is_object($item)) {
                    $result[] = (array) $item;
                } else {
                    // Convert scalar values to associative array with index as key
                    $result[] = ['index' => $index, 'value' => $item];
                }
            }
            return $result;
        }

        // If data is an object, convert to associative array and wrap
        if (is_object($data)) {
            return [(array) $data];
        }

        throw new Exception('Unable to normalize JSON data to tabular format');
    }

    /**
     * Check if array is associative (has string keys)
     *
     * @param array $array
     * @return bool
     */
    private function isAssociativeArray(array $array): bool
    {
        if (empty($array)) {
            return false;
        }
        
        return array_keys($array) !== range(0, count($array) - 1);
    }

    /**
     * Check if array is an array of associative arrays
     *
     * @param array $array
     * @return bool
     */
    private function isArrayOfAssociativeArrays(array $array): bool
    {
        if (empty($array)) {
            return true;
        }

        foreach ($array as $item) {
            if (!is_array($item) || !$this->isAssociativeArray($item)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validate uploaded file
     *
     * @param UploadedFile $file
     * @throws InvalidArgumentException
     */
    private function validateFile(UploadedFile $file): void
    {
        if (!$file->isValid()) {
            throw new InvalidArgumentException('Invalid file upload');
        }

        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, $this->getSupportedExtensions())) {
            throw new InvalidArgumentException(
                "Unsupported file extension: {$extension}. Supported: " . 
                implode(', ', $this->getSupportedExtensions())
            );
        }
    }
}

