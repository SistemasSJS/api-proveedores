<?php

namespace App\Services\Parsers;

use App\Services\Parsers\Contracts\FileParserInterface;
use Illuminate\Http\UploadedFile;
use InvalidArgumentException;
use Exception;

class CsvParser implements FileParserInterface
{
    /**
     * Default delimiter for CSV parsing
     */
    private string $delimiter = ',';

    /**
     * Default enclosure for CSV parsing
     */
    private string $enclosure = '"';

    /**
     * Default escape character for CSV parsing
     */
    private string $escape = '\\';

    public function parseUploadedFile(UploadedFile $file): array
    {
        $this->validateFile($file);
        
        // Get temporary path of uploaded file
        $tempPath = $file->getRealPath();
        
        return $this->parseFilePath($tempPath);
    }

    public function parseFilePath(string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw new InvalidArgumentException("File not found: {$filePath}");
        }

        if (!is_readable($filePath)) {
            throw new InvalidArgumentException("File is not readable: {$filePath}");
        }

        try {
            // Read file content and detect delimiter
            $this->detectDelimiter($filePath);
            
            // Parse CSV using file() function like in the original implementation
            $data = array_map(function($line) {
                return str_getcsv($line, $this->delimiter, $this->enclosure, $this->escape);
            }, file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));

            if (empty($data)) {
                return [];
            }

            // Extract headers from first row
            $headers = array_shift($data);
            
            if (empty($headers)) {
                throw new Exception('CSV file has no headers');
            }

            // Clean headers (remove BOM, trim whitespace)
            $headers = array_map(function($header) {
                return trim(str_replace("\xEF\xBB\xBF", '', $header));
            }, $headers);

            // Convert each row to associative array
            $result = [];
            foreach ($data as $rowIndex => $row) {
                // Skip completely empty rows or rows with only empty values
                if (empty($row) || $this->isEmptyRow($row)) {
                    continue; // Skip empty rows
                }

                // Pad row with empty values if it has fewer columns than headers
                while (count($row) < count($headers)) {
                    $row[] = '';
                }

                // Truncate row if it has more columns than headers
                if (count($row) > count($headers)) {
                    $row = array_slice($row, 0, count($headers));
                }

                $result[] = array_combine($headers, $row);
            }

            return $result;
            
        } catch (Exception $e) {
            throw new Exception("Failed to parse CSV file: " . $e->getMessage(), 0, $e);
        }
    }

    public function getSupportedExtensions(): array
    {
        return ['csv', 'txt'];
    }

    public function getSupportedMimeTypes(): array
    {
        return [
            'text/csv',
            'text/plain',
            'application/csv',
            'text/comma-separated-values'
        ];
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

    /**
     * Detect the most likely delimiter for the CSV file
     *
     * @param string $filePath
     */
    private function detectDelimiter(string $filePath): void
    {
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return;
        }

        // Read first few lines to detect delimiter
        $sampleLines = [];
        for ($i = 0; $i < 3; $i++) {
            $line = fgets($handle);
            if ($line === false) break;
            $sampleLines[] = $line;
        }
        fclose($handle);

        if (empty($sampleLines)) {
            return;
        }

        $delimiters = [',', ';', "\t", '|'];
        $delimiterCounts = [];

        foreach ($delimiters as $delimiter) {
            $count = 0;
            foreach ($sampleLines as $line) {
                $count += substr_count($line, $delimiter);
            }
            $delimiterCounts[$delimiter] = $count;
        }

        // Use the delimiter that appears most frequently
        $maxCount = max($delimiterCounts);
        if ($maxCount > 0) {
            $this->delimiter = array_search($maxCount, $delimiterCounts);
        }
    }

    /**
     * Set custom delimiter
     *
     * @param string $delimiter
     * @return self
     */
    public function setDelimiter(string $delimiter): self
    {
        $this->delimiter = $delimiter;
        return $this;
    }

    /**
     * Set custom enclosure
     *
     * @param string $enclosure
     * @return self
     */
    public function setEnclosure(string $enclosure): self
    {
        $this->enclosure = $enclosure;
        return $this;
    }

    /**
     * Set custom escape character
     *
     * @param string $escape
     * @return self
     */
    public function setEscape(string $escape): self
    {
        $this->escape = $escape;
        return $this;
    }

    /**
     * Check if a row is completely empty
     *
     * @param array $row
     * @return bool
     */
    private function isEmptyRow(array $row): bool
    {
        foreach ($row as $cell) {
            if (trim((string) $cell) !== '') {
                return false;
            }
        }
        return true;
    }
}

