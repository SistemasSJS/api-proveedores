<?php

namespace Tests\Unit\Services\Parsers;

use Tests\TestCase;
use App\Services\Parsers\ExcelParser;
use Illuminate\Http\UploadedFile;
use InvalidArgumentException;
use Exception;
use Maatwebsite\Excel\Facades\Excel;
use Mockery;

class ExcelParserTest extends TestCase
{
    private ExcelParser $excelParser;
    private string $testFilesPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->excelParser = new ExcelParser();
        $this->testFilesPath = storage_path('tests/excel');
        
        if (!is_dir($this->testFilesPath)) {
            mkdir($this->testFilesPath, 0755, true);
        }
    }

    protected function tearDown(): void
    {
        if (is_dir($this->testFilesPath)) {
            $this->deleteDirectory($this->testFilesPath);
        }
        parent::tearDown();
    }

    public function test_get_supported_extensions()
    {
        $extensions = $this->excelParser->getSupportedExtensions();
        
        $this->assertEquals(['xls', 'xlsx'], $extensions);
    }

    public function test_get_supported_mime_types()
    {
        $mimeTypes = $this->excelParser->getSupportedMimeTypes();
        
        $this->assertContains('application/vnd.ms-excel', $mimeTypes);
        $this->assertContains('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $mimeTypes);
        $this->assertContains('application/excel', $mimeTypes);
    }

    public function test_parse_valid_excel_data()
    {
        // Mock Excel facade to return test data
        Excel::shouldReceive('toArray')
            ->once()
            ->andReturn([
                [ // First sheet
                    ['name', 'email', 'age'], // Headers
                    ['John Doe', 'john@example.com', '30'], // Row 1
                    ['Jane Smith', 'jane@example.com', '25'] // Row 2
                ]
            ]);

        $excelFile = $this->createMockFile('test.xlsx');
        
        $result = $this->excelParser->parseFilePath($excelFile);
        
        $this->assertCount(2, $result);
        $this->assertEquals([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'age' => '30'
        ], $result[0]);
        $this->assertEquals([
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'age' => '25'
        ], $result[1]);
    }

    public function test_parse_excel_with_empty_rows()
    {
        Excel::shouldReceive('toArray')
            ->once()
            ->andReturn([
                [
                    ['name', 'email'], // Headers
                    ['John Doe', 'john@example.com'], // Row 1
                    [null, null], // Empty row
                    ['Jane Smith', 'jane@example.com'] // Row 2
                ]
            ]);

        $excelFile = $this->createMockFile('test.xlsx');
        
        $result = $this->excelParser->parseFilePath($excelFile);
        
        $this->assertCount(2, $result); // Empty row should be skipped
        $this->assertEquals('John Doe', $result[0]['name']);
        $this->assertEquals('Jane Smith', $result[1]['name']);
    }

    public function test_parse_excel_with_mismatched_columns()
    {
        Excel::shouldReceive('toArray')
            ->once()
            ->andReturn([
                [
                    ['name', 'email', 'age'], // Headers
                    ['John Doe', 'john@example.com'], // Missing age
                    ['Jane Smith', 'jane@example.com', '25', 'extra'], // Extra column
                    ['Bob Wilson', 'bob@example.com', '30'] // Complete row
                ]
            ]);

        $excelFile = $this->createMockFile('test.xlsx');
        
        $result = $this->excelParser->parseFilePath($excelFile);
        
        $this->assertCount(3, $result);
        $this->assertEquals('', $result[0]['age']); // Should be padded with empty string
        $this->assertEquals('25', $result[1]['age']); // Extra column should be truncated
        $this->assertArrayNotHasKey('extra', $result[1]);
        $this->assertEquals('30', $result[2]['age']);
    }

    public function test_parse_excel_with_null_values()
    {
        Excel::shouldReceive('toArray')
            ->once()
            ->andReturn([
                [
                    ['name', 'email', 'age'], // Headers
                    ['John Doe', null, 30], // Null email
                    [null, 'jane@example.com', '25'] // Null name
                ]
            ]);

        $excelFile = $this->createMockFile('test.xlsx');
        
        $result = $this->excelParser->parseFilePath($excelFile);
        
        $this->assertCount(2, $result);
        $this->assertEquals('John Doe', $result[0]['name']);
        $this->assertEquals('', $result[0]['email']); // Null should be converted to empty string
        $this->assertEquals('30', $result[0]['age']);
        $this->assertEquals('', $result[1]['name']); // Null should be converted to empty string
        $this->assertEquals('jane@example.com', $result[1]['email']);
    }

    public function test_parse_excel_with_trailing_empty_headers()
    {
        Excel::shouldReceive('toArray')
            ->once()
            ->andReturn([
                [
                    ['name', 'email', '', '', null], // Headers with trailing empty values
                    ['John Doe', 'john@example.com', 'value', '', ''], // Row with data
                ]
            ]);

        $excelFile = $this->createMockFile('test.xlsx');
        
        $result = $this->excelParser->parseFilePath($excelFile);
        
        $this->assertCount(1, $result);
        $this->assertArrayHasKey('name', $result[0]);
        $this->assertArrayHasKey('email', $result[0]);
        $this->assertArrayNotHasKey('', $result[0]); // Empty headers should be removed
    }

    public function test_parse_empty_excel_file()
    {
        Excel::shouldReceive('toArray')
            ->once()
            ->andReturn([[]]); // Empty sheet

        $excelFile = $this->createMockFile('empty.xlsx');
        
        $result = $this->excelParser->parseFilePath($excelFile);
        
        $this->assertEmpty($result);
    }

    public function test_parse_excel_with_headers_only()
    {
        Excel::shouldReceive('toArray')
            ->once()
            ->andReturn([
                [['name', 'email']] // Only headers, no data
            ]);

        $excelFile = $this->createMockFile('headers_only.xlsx');
        
        $result = $this->excelParser->parseFilePath($excelFile);
        
        $this->assertEmpty($result);
    }

    public function test_parse_excel_multiple_sheets_uses_first()
    {
        Excel::shouldReceive('toArray')
            ->once()
            ->andReturn([
                [ // First sheet
                    ['name', 'email'],
                    ['John Doe', 'john@example.com']
                ],
                [ // Second sheet (should be ignored)
                    ['product', 'price'],
                    ['Product A', '10.00']
                ]
            ]);

        $excelFile = $this->createMockFile('multiple_sheets.xlsx');
        
        $result = $this->excelParser->parseFilePath($excelFile);
        
        $this->assertCount(1, $result);
        $this->assertArrayHasKey('name', $result[0]);
        $this->assertArrayHasKey('email', $result[0]);
        $this->assertArrayNotHasKey('product', $result[0]); // Second sheet should be ignored
    }

    public function test_parse_nonexistent_file_throws_exception()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('File not found');
        
        $this->excelParser->parseFilePath('/path/to/nonexistent.xlsx');
    }

    public function test_parse_unreadable_file_throws_exception()
    {
        $excelFile = $this->testFilesPath . '/unreadable.xlsx';
        file_put_contents($excelFile, 'dummy content');
        chmod($excelFile, 0000); // Make file unreadable
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('File is not readable');
        
        $this->excelParser->parseFilePath($excelFile);
        
        // Clean up
        chmod($excelFile, 0644);
        unlink($excelFile);
    }

    public function test_parse_uploaded_file_success()
    {
        Excel::shouldReceive('toArray')
            ->once()
            ->andReturn([
                [
                    ['name', 'email'],
                    ['John Doe', 'john@example.com']
                ]
            ]);

        $uploadedFile = $this->createMock(UploadedFile::class);
        $uploadedFile->method('isValid')->willReturn(true);
        $uploadedFile->method('getClientOriginalExtension')->willReturn('xlsx');
        
        $result = $this->excelParser->parseUploadedFile($uploadedFile);
        
        $this->assertCount(1, $result);
        $this->assertEquals('John Doe', $result[0]['name']);
    }

    public function test_parse_invalid_uploaded_file_throws_exception()
    {
        $uploadedFile = $this->createMock(UploadedFile::class);
        $uploadedFile->method('isValid')->willReturn(false);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid file upload');
        
        $this->excelParser->parseUploadedFile($uploadedFile);
    }

    public function test_parse_unsupported_extension_throws_exception()
    {
        $uploadedFile = $this->createMock(UploadedFile::class);
        $uploadedFile->method('isValid')->willReturn(true);
        $uploadedFile->method('getClientOriginalExtension')->willReturn('csv');
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported file extension: csv');
        
        $this->excelParser->parseUploadedFile($uploadedFile);
    }

    public function test_excel_parsing_exception_throws_wrapped_exception()
    {
        Excel::shouldReceive('toArray')
            ->once()
            ->andThrow(new Exception('Excel parsing failed'));

        $excelFile = $this->createMockFile('corrupted.xlsx');
        
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Failed to parse Excel file: Excel parsing failed');
        
        $this->excelParser->parseFilePath($excelFile);
    }

    public function test_normalize_excel_data_with_empty_data()
    {
        Excel::shouldReceive('toArray')
            ->once()
            ->andReturn([]);

        $excelFile = $this->createMockFile('empty_response.xlsx');
        
        $result = $this->excelParser->parseFilePath($excelFile);
        
        $this->assertEmpty($result);
    }

    /**
     * Create a mock file for testing
     */
    private function createMockFile(string $filename): string
    {
        $filePath = $this->testFilesPath . '/' . $filename;
        file_put_contents($filePath, 'dummy excel content');
        return $filePath;
    }

    /**
     * Recursively delete a directory
     */
    private function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}

