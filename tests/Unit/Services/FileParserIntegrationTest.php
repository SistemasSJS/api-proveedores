<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\FileParserService;
use Illuminate\Http\UploadedFile;

class FileParserIntegrationTest extends TestCase
{
    private FileParserService $fileParserService;
    private string $testFilesPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fileParserService = new FileParserService();
        $this->testFilesPath = storage_path('tests/integration');
        
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

    public function test_parse_csv_file_integration()
    {
        // Create test CSV file
        $csvFile = $this->testFilesPath . '/products.csv';
        $csvContent = "sku,nombre_producto,precio,marca\n" .
                     "ABC123,Product A,19.99,Brand X\n" .
                     "DEF456,Product B,25.50,Brand Y";
        file_put_contents($csvFile, $csvContent);
        
        $result = $this->fileParserService->parseFile($csvFile);
        
        $this->assertCount(2, $result);
        $this->assertEquals('ABC123', $result[0]['sku']);
        $this->assertEquals('Product A', $result[0]['nombre_producto']);
        $this->assertEquals('19.99', $result[0]['precio']);
        $this->assertEquals('Brand X', $result[0]['marca']);
    }

    public function test_parse_json_file_integration()
    {
        // Create test JSON file
        $jsonFile = $this->testFilesPath . '/products.json';
        $jsonData = [
            'data' => [
                ['sku' => 'ABC123', 'nombre_producto' => 'Product A', 'precio' => 19.99, 'marca' => 'Brand X'],
                ['sku' => 'DEF456', 'nombre_producto' => 'Product B', 'precio' => 25.50, 'marca' => 'Brand Y']
            ]
        ];
        file_put_contents($jsonFile, json_encode($jsonData));
        
        $result = $this->fileParserService->parseFile($jsonFile);
        
        $this->assertCount(2, $result);
        $this->assertEquals('ABC123', $result[0]['sku']);
        $this->assertEquals('Product A', $result[0]['nombre_producto']);
        $this->assertEquals(19.99, $result[0]['precio']);
        $this->assertEquals('Brand X', $result[0]['marca']);
    }

    public function test_parse_uploaded_csv_file_integration()
    {
        // Create test CSV file
        $csvFile = $this->testFilesPath . '/upload.csv';
        $csvContent = "name,email,age\n" .
                     "John Doe,john@example.com,30\n" .
                     "Jane Smith,jane@example.com,25";
        file_put_contents($csvFile, $csvContent);
        
        $uploadedFile = new UploadedFile(
            $csvFile,
            'upload.csv',
            'text/csv',
            null,
            true
        );
        
        $result = $this->fileParserService->parseFile($uploadedFile);
        
        $this->assertCount(2, $result);
        $this->assertEquals('John Doe', $result[0]['name']);
        $this->assertEquals('john@example.com', $result[0]['email']);
        $this->assertEquals('30', $result[0]['age']);
    }

    public function test_supported_extensions()
    {
        $extensions = $this->fileParserService->getSupportedExtensions();
        
        $this->assertContains('csv', $extensions);
        $this->assertContains('txt', $extensions);
        $this->assertContains('json', $extensions);
        $this->assertContains('xls', $extensions);
        $this->assertContains('xlsx', $extensions);
    }

    public function test_parse_csv_with_different_delimiters()
    {
        // Test semicolon-separated values
        $csvFile = $this->testFilesPath . '/semicolon.csv';
        $csvContent = "name;email;city\n" .
                     "John Doe;john@example.com;New York\n" .
                     "Jane Smith;jane@example.com;Los Angeles";
        file_put_contents($csvFile, $csvContent);
        
        $result = $this->fileParserService->parseFile($csvFile);
        
        $this->assertCount(2, $result);
        $this->assertEquals('John Doe', $result[0]['name']);
        $this->assertEquals('john@example.com', $result[0]['email']);
        $this->assertEquals('New York', $result[0]['city']);
    }

    public function test_uniform_output_format_across_parsers()
    {
        // Create identical data in different formats
        $expectedData = [
            ['id' => '1', 'name' => 'Alice', 'role' => 'Admin'],
            ['id' => '2', 'name' => 'Bob', 'role' => 'User']
        ];
        
        // CSV format
        $csvFile = $this->testFilesPath . '/users.csv';
        file_put_contents($csvFile, "id,name,role\n1,Alice,Admin\n2,Bob,User");
        
        // JSON format
        $jsonFile = $this->testFilesPath . '/users.json';
        file_put_contents($jsonFile, json_encode($expectedData));
        
        $csvResult = $this->fileParserService->parseFile($csvFile);
        $jsonResult = $this->fileParserService->parseFile($jsonFile);
        
        // Both should have the same structure
        $this->assertEquals($csvResult, $jsonResult);
        $this->assertEquals($expectedData, $csvResult);
        $this->assertEquals($expectedData, $jsonResult);
    }

    public function test_error_handling_malformed_files()
    {
        // Test malformed JSON
        $malformedJsonFile = $this->testFilesPath . '/malformed.json';
        file_put_contents($malformedJsonFile, '{"name": "John", "age":}'); // Missing value
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid JSON');
        
        $this->fileParserService->parseFile($malformedJsonFile);
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

