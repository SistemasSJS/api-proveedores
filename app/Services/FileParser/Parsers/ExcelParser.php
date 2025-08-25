<?php

namespace App\Services\FileParser\Parsers;

use App\Services\Parsers\Contracts\FileParserInterface;
use Illuminate\Http\UploadedFile;
use InvalidArgumentException;
use Exception;
use Maatwebsite\Excel\Facades\Excel;

class ExcelParser implements FileParserInterface
{
    public function parseUploadedFile(UploadedFile $file): array
    {
        $this->validateFile($file);

        try {
            // For Laravel Excel v1.x, load file and convert to array
            $data = Excel::load($file->getRealPath())->toArray();
            return $this->normalizeExcelData($data);
        } catch (Exception $e) {
            throw new Exception("Failed to parse Excel file: " . $e->getMessage(), 0, $e);
        }
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
            // For Laravel Excel v1.x, load file and convert to array
            $data = Excel::load($filePath)->toArray();
            return $this->normalizeExcelData($data);
        } catch (Exception $e) {
            throw new Exception("Failed to parse Excel file: " . $e->getMessage(), 0, $e);
        }
    }

    public function getSupportedExtensions(): array
    {
        return ['xls', 'xlsx'];
    }

    public function getSupportedMimeTypes(): array
    {
        return [
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/excel',
            'application/x-excel',
            'application/x-msexcel'
        ];
    }

    /**
     * Normalize Excel data to array of associative arrays
     *
     * @param array $data
     * @return array
     */
    private function normalizeExcelData(array $data): array
    {
        if (empty($data)) {
            return [];
        }

        // Excel::toArray returns an array of sheets, we'll use the first sheet
        $sheet = reset($data);

        if (empty($sheet)) {
            return [];
        }

        // Extract headers from first row
        $headers = array_shift($sheet);

        if (empty($headers)) {
            return [];
        }

        // Clean headers (remove null values, trim whitespace)
        $headers = array_map(function ($header) {
            return trim((string) $header);
        }, $headers);

        // Remove empty trailing headers
        while (end($headers) === '' && count($headers) > 0) {
            array_pop($headers);
        }

        if (empty($headers)) {
            return [];
        }

        $result = [];

        foreach ($sheet as $rowIndex => $row) {
            // Skip completely empty rows
            if (empty($row) || $this->isEmptyRow($row)) {
                continue;
            }

            // Pad row with null values if it has fewer columns than headers
            while (count($row) < count($headers)) {
                $row[] = null;
            }

            // Truncate row if it has more columns than headers
            if (count($row) > count($headers)) {
                $row = array_slice($row, 0, count($headers));
            }

            // Convert null values to empty strings to match CSV behavior
            $row = array_map(function ($value) {
                return $value === null ? '' : (string) $value;
            }, $row);

            $result[] = array_combine($headers, $row);
        }

        return $result;
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
            if ($cell !== null && trim((string) $cell) !== '') {
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
