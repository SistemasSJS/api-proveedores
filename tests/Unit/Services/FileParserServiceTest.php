<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\FileParserService;
use Illuminate\Http\UploadedFile;
use InvalidArgumentException;
use Illuminate\Support\Facades\Storage;

class FileParserServiceTest extends TestCase
{
    private FileParserService $fileParserService;
    private string $testFilesPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fileParserService = new FileParserService();
        $this->testFilesPath = storage_path('tests/files');
        
        // Create test files directory
        if (!is_dir($this->testFilesPath)) {
            mkdir($this->testFilesPath, 0755, true);
        }
    }

    protected function tearDown(): void
    {
        // Clean up test files
        if (is_dir($this->testFilesPath)) {
            $this->deleteDirectory($this->testFilesPath);
        }
        parent::tearDown();
    }

    public function test_get_supported_extensions()
    {
        $extensions = $this->fileParserService->getSupportedExtensions();
        
        $this->assertIsArray($extensions);
        $this->assertContains('csv', $extensions);
        $this->assertContains('txt', $extensions);
        $this->assertContains('json', $extensions);
        $this->assertContains('xls', $extensions);
        $this->assertContains('xlsx', $extensions);
    }

    public function test_is_supported_with_supported_extension()
    {
        $csvFile = $this->createTestCsvFile();
        $this->assertTrue($this->fileParserService->isSupported($csvFile));
    }

    public function test_is_supported_with_unsupported_extension()
    {
        $unsupportedFile = $this->testFilesPath . '/test.xyz';
        file_put_contents($unsupportedFile, 'dummy content');
        
        $this->assertFalse($this->fileParserService->isSupported($unsupportedFile));
    }

    public function test_parse_csv_file_success()
    {
        $csvFile = $this->createTestCsvFile();
        $result = $this->fileParserService->parseFile($csvFile);
        
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertArrayHasKey('name', $result[0]);
        $this->assertArrayHasKey('email', $result[0]);
        $this->assertEquals('John Doe', $result[0]['name']);
        $this->assertEquals('john@example.com', $result[0]['email']);
    }

    public function test_parse_json_file_success()
    {
        $jsonFile = $this->createTestJsonFile();
        $result = $this->fileParserService->parseFile($jsonFile);
        
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertArrayHasKey('name', $result[0]);
        $this->assertArrayHasKey('email', $result[0]);
        $this->assertEquals('John Doe', $result[0]['name']);
        $this->assertEquals('john@example.com', $result[0]['email']);
    }

    public function test_parse_uploaded_file_success()
    {
        $csvFile = $this->createTestCsvFile();
        $uploadedFile = new UploadedFile(
            $csvFile,
            'test.csv',
            'text/csv',
            null,
            true
        );
        
        $result = $this->fileParserService->parseFile($uploadedFile);
        
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals('John Doe', $result[0]['name']);
    }

    public function test_parse_file_with_unsupported_format_throws_exception()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported file format');
        
        $unsupportedFile = $this->testFilesPath . '/test.xyz';
        file_put_contents($unsupportedFile, 'dummy content');
        
        $this->fileParserService->parseFile($unsupportedFile);
    }

    public function test_parse_nonexistent_file_throws_exception()
    {
        $this->expectException(InvalidArgumentException::class);
        
        $this->fileParserService->parseFile('/path/to/nonexistent/file.csv');
    }

    public function test_parse_empty_csv_file()
    {
        $csvFile = $this->testFilesPath . '/empty.csv';
        file_put_contents($csvFile, '');
        
        $result = $this->fileParserService->parseFile($csvFile);
        
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_parse_csv_with_headers_only()
    {
        $csvFile = $this->testFilesPath . '/headers_only.csv';
        file_put_contents($csvFile, "name,email\n");
        
        $result = $this->fileParserService->parseFile($csvFile);
        
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_parse_malformed_json_throws_exception()
    {
        $jsonFile = $this->testFilesPath . '/malformed.json';
        file_put_contents($jsonFile, '{"name": "John", "email":}'); // Malformed JSON
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid JSON');
        
        $this->fileParserService->parseFile($jsonFile);
    }

    public function test_parse_json_with_nested_data_structure()
    {
        $jsonFile = $this->testFilesPath . '/nested.json';
        $jsonData = [
            'data' => [
                ['name' => 'John Doe', 'email' => 'john@example.com'],
                ['name' => 'Jane Smith', 'email' => 'jane@example.com']
            ]
        ];
        file_put_contents($jsonFile, json_encode($jsonData));
        
        $result = $this->fileParserService->parseFile($jsonFile);
        
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals('John Doe', $result[0]['name']);
        $this->assertEquals('jane@example.com', $result[1]['email']);
    }

    public function test_parse_csv_with_different_delimiters()
    {
        $csvFile = $this->testFilesPath . '/semicolon.csv';
        file_put_contents($csvFile, "name;email\nJohn Doe;john@example.com\nJane Smith;jane@example.com");
        
        $result = $this->fileParserService->parseFile($csvFile);
        
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals('John Doe', $result[0]['name']);
        $this->assertEquals('john@example.com', $result[0]['email']);
    }

    /**
     * Create a test CSV file
     */
    private function createTestCsvFile(): string
    {
        $csvFile = $this->testFilesPath . '/test.csv';
        $csvContent = "name,email\n" .
                     "John Doe,john@example.com\n" .
                     "Jane Smith,jane@example.com";
        file_put_contents($csvFile, $csvContent);
        return $csvFile;
    }

    /**
     * Create a test JSON file
     */
    private function createTestJsonFile(): string
    {
        $jsonFile = $this->testFilesPath . '/test.json';
        $jsonData = [
            ['name' => 'John Doe', 'email' => 'john@example.com'],
            ['name' => 'Jane Smith', 'email' => 'jane@example.com']
        ];
        file_put_contents($jsonFile, json_encode($jsonData));
        return $jsonFile;
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

