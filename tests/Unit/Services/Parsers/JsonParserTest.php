<?php

namespace Tests\Unit\Services\Parsers;

use Tests\TestCase;
use App\Services\Parsers\JsonParser;
use Illuminate\Http\UploadedFile;
use InvalidArgumentException;
use Exception;

class JsonParserTest extends TestCase
{
    private JsonParser $jsonParser;
    private string $testFilesPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->jsonParser = new JsonParser();
        $this->testFilesPath = storage_path('tests/json');
        
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
        $extensions = $this->jsonParser->getSupportedExtensions();
        
        $this->assertEquals(['json'], $extensions);
    }

    public function test_get_supported_mime_types()
    {
        $mimeTypes = $this->jsonParser->getSupportedMimeTypes();
        
        $this->assertContains('application/json', $mimeTypes);
        $this->assertContains('text/json', $mimeTypes);
        $this->assertContains('application/x-json', $mimeTypes);
    }

    public function test_parse_array_of_objects()
    {
        $jsonData = [
            ['name' => 'John Doe', 'email' => 'john@example.com', 'age' => 30],
            ['name' => 'Jane Smith', 'email' => 'jane@example.com', 'age' => 25]
        ];
        $jsonFile = $this->createJsonFile('array_of_objects.json', $jsonData);
        
        $result = $this->jsonParser->parseFilePath($jsonFile);
        
        $this->assertCount(2, $result);
        $this->assertEquals([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'age' => 30
        ], $result[0]);
        $this->assertEquals([
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'age' => 25
        ], $result[1]);
    }

    public function test_parse_single_object()
    {
        $jsonData = ['name' => 'John Doe', 'email' => 'john@example.com', 'age' => 30];
        $jsonFile = $this->createJsonFile('single_object.json', $jsonData);
        
        $result = $this->jsonParser->parseFilePath($jsonFile);
        
        $this->assertCount(1, $result);
        $this->assertEquals([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'age' => 30
        ], $result[0]);
    }

    public function test_parse_nested_data_structure()
    {
        $jsonData = [
            'data' => [
                ['name' => 'John Doe', 'email' => 'john@example.com'],
                ['name' => 'Jane Smith', 'email' => 'jane@example.com']
            ],
            'meta' => ['total' => 2]
        ];
        $jsonFile = $this->createJsonFile('nested_data.json', $jsonData);
        
        $result = $this->jsonParser->parseFilePath($jsonFile);
        
        $this->assertCount(2, $result);
        $this->assertEquals('John Doe', $result[0]['name']);
        $this->assertEquals('Jane Smith', $result[1]['name']);
    }

    public function test_parse_nested_rows_structure()
    {
        $jsonData = [
            'rows' => [
                ['name' => 'John Doe', 'email' => 'john@example.com'],
                ['name' => 'Jane Smith', 'email' => 'jane@example.com']
            ]
        ];
        $jsonFile = $this->createJsonFile('nested_rows.json', $jsonData);
        
        $result = $this->jsonParser->parseFilePath($jsonFile);
        
        $this->assertCount(2, $result);
        $this->assertEquals('John Doe', $result[0]['name']);
        $this->assertEquals('Jane Smith', $result[1]['name']);
    }

    public function test_parse_nested_records_structure()
    {
        $jsonData = [
            'records' => [
                ['name' => 'John Doe', 'email' => 'john@example.com'],
                ['name' => 'Jane Smith', 'email' => 'jane@example.com']
            ]
        ];
        $jsonFile = $this->createJsonFile('nested_records.json', $jsonData);
        
        $result = $this->jsonParser->parseFilePath($jsonFile);
        
        $this->assertCount(2, $result);
        $this->assertEquals('John Doe', $result[0]['name']);
        $this->assertEquals('Jane Smith', $result[1]['name']);
    }

    public function test_parse_array_of_mixed_types()
    {
        $jsonData = [
            ['name' => 'John Doe', 'email' => 'john@example.com'],
            (object) ['name' => 'Jane Smith', 'email' => 'jane@example.com'],
            'simple string'
        ];
        $jsonFile = $this->createJsonFile('mixed_types.json', $jsonData);
        
        $result = $this->jsonParser->parseFilePath($jsonFile);
        
        $this->assertCount(3, $result);
        $this->assertEquals('John Doe', $result[0]['name']);
        $this->assertEquals('Jane Smith', $result[1]['name']);
        $this->assertEquals(['index' => 2, 'value' => 'simple string'], $result[2]);
    }

    public function test_parse_array_of_scalars()
    {
        $jsonData = ['value1', 'value2', 'value3'];
        $jsonFile = $this->createJsonFile('scalars.json', $jsonData);
        
        $result = $this->jsonParser->parseFilePath($jsonFile);
        
        $this->assertCount(3, $result);
        $this->assertEquals(['index' => 0, 'value' => 'value1'], $result[0]);
        $this->assertEquals(['index' => 1, 'value' => 'value2'], $result[1]);
        $this->assertEquals(['index' => 2, 'value' => 'value3'], $result[2]);
    }

    public function test_parse_json_with_bom()
    {
        $jsonData = [['name' => 'John Doe', 'email' => 'john@example.com']];
        $jsonFile = $this->testFilesPath . '/bom.json';
        $content = "\xEF\xBB\xBF" . json_encode($jsonData);
        file_put_contents($jsonFile, $content);
        
        $result = $this->jsonParser->parseFilePath($jsonFile);
        
        $this->assertCount(1, $result);
        $this->assertEquals('John Doe', $result[0]['name']);
    }

    public function test_parse_empty_json_file()
    {
        $jsonFile = $this->createJsonFile('empty.json', []);
        
        $result = $this->jsonParser->parseFilePath($jsonFile);
        
        $this->assertEmpty($result);
    }

    public function test_parse_whitespace_only_file()
    {
        $jsonFile = $this->testFilesPath . '/whitespace.json';
        file_put_contents($jsonFile, "   \n\t  ");
        
        $result = $this->jsonParser->parseFilePath($jsonFile);
        
        $this->assertEmpty($result);
    }

    public function test_parse_malformed_json_throws_exception()
    {
        $jsonFile = $this->testFilesPath . '/malformed.json';
        file_put_contents($jsonFile, '{"name": "John", "email":}'); // Missing value
        
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid JSON');
        
        $this->jsonParser->parseFilePath($jsonFile);
    }

    public function test_parse_invalid_json_syntax_throws_exception()
    {
        $jsonFile = $this->testFilesPath . '/invalid_syntax.json';
        file_put_contents($jsonFile, '{name: "John"}'); // Missing quotes around key
        
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid JSON');
        
        $this->jsonParser->parseFilePath($jsonFile);
    }

    public function test_parse_nonexistent_file_throws_exception()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('File not found');
        
        $this->jsonParser->parseFilePath('/path/to/nonexistent.json');
    }

    public function test_parse_unreadable_file_throws_exception()
    {
        $jsonFile = $this->testFilesPath . '/unreadable.json';
        file_put_contents($jsonFile, '[]');
        chmod($jsonFile, 0000); // Make file unreadable
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('File is not readable');
        
        $this->jsonParser->parseFilePath($jsonFile);
        
        // Clean up
        chmod($jsonFile, 0644);
        unlink($jsonFile);
    }

    public function test_parse_uploaded_file_success()
    {
        $jsonData = [['name' => 'John Doe', 'email' => 'john@example.com']];
        $jsonFile = $this->createJsonFile('upload.json', $jsonData);
        
        $uploadedFile = new UploadedFile(
            $jsonFile,
            'test.json',
            'application/json',
            null,
            true
        );
        
        $result = $this->jsonParser->parseUploadedFile($uploadedFile);
        
        $this->assertCount(1, $result);
        $this->assertEquals('John Doe', $result[0]['name']);
    }

    public function test_parse_invalid_uploaded_file_throws_exception()
    {
        $uploadedFile = $this->createMock(UploadedFile::class);
        $uploadedFile->method('isValid')->willReturn(false);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid file upload');
        
        $this->jsonParser->parseUploadedFile($uploadedFile);
    }

    public function test_parse_unsupported_extension_throws_exception()
    {
        $uploadedFile = $this->createMock(UploadedFile::class);
        $uploadedFile->method('isValid')->willReturn(true);
        $uploadedFile->method('getClientOriginalExtension')->willReturn('txt');
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported file extension: txt');
        
        $this->jsonParser->parseUploadedFile($uploadedFile);
    }

    public function test_parse_complex_nested_structure()
    {
        $jsonData = [
            'response' => [
                'data' => [
                    'users' => [
                        ['name' => 'John Doe', 'email' => 'john@example.com'],
                        ['name' => 'Jane Smith', 'email' => 'jane@example.com']
                    ]
                ]
            ]
        ];
        $jsonFile = $this->createJsonFile('complex_nested.json', $jsonData);
        
        $result = $this->jsonParser->parseFilePath($jsonFile);
        
        // Should try common keys but fall back to converting the structure
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
    }

    public function test_parse_object_to_array_conversion()
    {
        $jsonData = (object) ['name' => 'John Doe', 'email' => 'john@example.com'];
        $jsonFile = $this->createJsonFile('object.json', $jsonData);
        
        $result = $this->jsonParser->parseFilePath($jsonFile);
        
        $this->assertCount(1, $result);
        $this->assertEquals('John Doe', $result[0]['name']);
        $this->assertEquals('john@example.com', $result[0]['email']);
    }

    public function test_parse_unnormalizable_data_throws_exception()
    {
        $jsonFile = $this->testFilesPath . '/scalar.json';
        file_put_contents($jsonFile, '"simple string"');
        
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Unable to normalize JSON data to tabular format');
        
        $this->jsonParser->parseFilePath($jsonFile);
    }

    /**
     * Create a JSON test file
     */
    private function createJsonFile(string $filename, $data): string
    {
        $filePath = $this->testFilesPath . '/' . $filename;
        file_put_contents($filePath, json_encode($data));
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

