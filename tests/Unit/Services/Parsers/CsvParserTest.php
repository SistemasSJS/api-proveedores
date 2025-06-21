<?php

namespace Tests\Unit\Services\Parsers;

use Tests\TestCase;
use App\Services\Parsers\CsvParser;
use Illuminate\Http\UploadedFile;
use InvalidArgumentException;
use Exception;

class CsvParserTest extends TestCase
{
    private CsvParser $csvParser;
    private string $testFilesPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->csvParser = new CsvParser();
        $this->testFilesPath = storage_path('tests/csv');
        
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
        $extensions = $this->csvParser->getSupportedExtensions();
        
        $this->assertEquals(['csv', 'txt'], $extensions);
    }

    public function test_get_supported_mime_types()
    {
        $mimeTypes = $this->csvParser->getSupportedMimeTypes();
        
        $this->assertContains('text/csv', $mimeTypes);
        $this->assertContains('text/plain', $mimeTypes);
        $this->assertContains('application/csv', $mimeTypes);
    }

    public function test_parse_valid_csv_file()
    {
        $csvFile = $this->createCsvFile('basic.csv', [
            'name,email,age',
            'John Doe,john@example.com,30',
            'Jane Smith,jane@example.com,25'
        ]);
        
        $result = $this->csvParser->parseFilePath($csvFile);
        
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

    public function test_parse_csv_with_semicolon_delimiter()
    {
        $csvFile = $this->createCsvFile('semicolon.csv', [
            'name;email;age',
            'John Doe;john@example.com;30',
            'Jane Smith;jane@example.com;25'
        ]);
        
        $result = $this->csvParser->parseFilePath($csvFile);
        
        $this->assertCount(2, $result);
        $this->assertEquals('John Doe', $result[0]['name']);
        $this->assertEquals('john@example.com', $result[0]['email']);
    }

    public function test_parse_csv_with_tab_delimiter()
    {
        $csvFile = $this->createCsvFile('tab.csv', [
            "name\temail\tage",
            "John Doe\tjohn@example.com\t30",
            "Jane Smith\tjane@example.com\t25"
        ]);
        
        $result = $this->csvParser->parseFilePath($csvFile);
        
        $this->assertCount(2, $result);
        $this->assertEquals('John Doe', $result[0]['name']);
        $this->assertEquals('john@example.com', $result[0]['email']);
    }

    public function test_parse_csv_with_quoted_values()
    {
        $csvFile = $this->createCsvFile('quoted.csv', [
            'name,description,price',
            '"John\'s Product","A product with, comma","$19.99"',
            '"Jane\'s Item","Another item","$25.50"'
        ]);
        
        $result = $this->csvParser->parseFilePath($csvFile);
        
        $this->assertCount(2, $result);
        $this->assertEquals('John\'s Product', $result[0]['name']);
        $this->assertEquals('A product with, comma', $result[0]['description']);
        $this->assertEquals('$19.99', $result[0]['price']);
    }

    public function test_parse_csv_with_bom()
    {
        $csvFile = $this->testFilesPath . '/bom.csv';
        $content = "\xEF\xBB\xBFname,email\n" .
                  "John Doe,john@example.com\n" .
                  "Jane Smith,jane@example.com";
        file_put_contents($csvFile, $content);
        
        $result = $this->csvParser->parseFilePath($csvFile);
        
        $this->assertCount(2, $result);
        $this->assertEquals('name', array_keys($result[0])[0]); // BOM should be removed
        $this->assertEquals('John Doe', $result[0]['name']);
    }

    public function test_parse_csv_with_empty_rows()
    {
        $csvFile = $this->createCsvFile('empty_rows.csv', [
            'name,email',
            'John Doe,john@example.com',
            '', // Empty row
            'Jane Smith,jane@example.com',
            ',', // Row with only commas
            'Bob Wilson,bob@example.com'
        ]);
        
        $result = $this->csvParser->parseFilePath($csvFile);
        
        $this->assertCount(3, $result); // Empty rows should be skipped
        $this->assertEquals('John Doe', $result[0]['name']);
        $this->assertEquals('Jane Smith', $result[1]['name']);
        $this->assertEquals('Bob Wilson', $result[2]['name']);
    }

    public function test_parse_csv_with_mismatched_columns()
    {
        $csvFile = $this->createCsvFile('mismatched.csv', [
            'name,email,age',
            'John Doe,john@example.com', // Missing age
            'Jane Smith,jane@example.com,25,extra_value', // Extra column
            'Bob Wilson,bob@example.com,30'
        ]);
        
        $result = $this->csvParser->parseFilePath($csvFile);
        
        $this->assertCount(3, $result);
        $this->assertEquals('', $result[0]['age']); // Should be padded with empty string
        $this->assertEquals('25', $result[1]['age']); // Extra column should be truncated
        $this->assertArrayNotHasKey('extra_value', $result[1]);
    }

    public function test_parse_empty_csv_file()
    {
        $csvFile = $this->createCsvFile('empty.csv', ['']);
        
        $result = $this->csvParser->parseFilePath($csvFile);
        
        $this->assertEmpty($result);
    }

    public function test_parse_csv_with_headers_only()
    {
        $csvFile = $this->createCsvFile('headers_only.csv', ['name,email']);
        
        $result = $this->csvParser->parseFilePath($csvFile);
        
        $this->assertEmpty($result);
    }

    public function test_parse_nonexistent_file_throws_exception()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('File not found');
        
        $this->csvParser->parseFilePath('/path/to/nonexistent.csv');
    }

    public function test_parse_uploaded_file_success()
    {
        $csvFile = $this->createCsvFile('upload.csv', [
            'name,email',
            'John Doe,john@example.com'
        ]);
        
        $uploadedFile = new UploadedFile(
            $csvFile,
            'test.csv',
            'text/csv',
            null,
            true
        );
        
        $result = $this->csvParser->parseUploadedFile($uploadedFile);
        
        $this->assertCount(1, $result);
        $this->assertEquals('John Doe', $result[0]['name']);
    }

    public function test_parse_invalid_uploaded_file_throws_exception()
    {
        $uploadedFile = $this->createMock(UploadedFile::class);
        $uploadedFile->method('isValid')->willReturn(false);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid file upload');
        
        $this->csvParser->parseUploadedFile($uploadedFile);
    }

    public function test_parse_unsupported_extension_throws_exception()
    {
        $uploadedFile = $this->createMock(UploadedFile::class);
        $uploadedFile->method('isValid')->willReturn(true);
        $uploadedFile->method('getClientOriginalExtension')->willReturn('pdf');
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported file extension: pdf');
        
        $this->csvParser->parseUploadedFile($uploadedFile);
    }

    public function test_set_custom_delimiter()
    {
        $csvFile = $this->createCsvFile('pipe.csv', [
            'name|email',
            'John Doe|john@example.com'
        ]);
        
        $result = $this->csvParser->setDelimiter('|')->parseFilePath($csvFile);
        
        $this->assertCount(1, $result);
        $this->assertEquals('John Doe', $result[0]['name']);
        $this->assertEquals('john@example.com', $result[0]['email']);
    }

    public function test_set_custom_enclosure()
    {
        $csvFile = $this->createCsvFile('single_quote.csv', [
            "name,description",
            "'John Product','A product with, comma'"
        ]);
        
        $result = $this->csvParser->setEnclosure("'")->parseFilePath($csvFile);
        
        $this->assertCount(1, $result);
        $this->assertEquals('John Product', $result[0]['name']);
        $this->assertEquals('A product with, comma', $result[0]['description']);
    }

    public function test_malformed_csv_throws_exception()
    {
        // Create a file that's not readable
        $csvFile = $this->testFilesPath . '/unreadable.csv';
        file_put_contents($csvFile, 'name,email\nJohn,john@example.com');
        chmod($csvFile, 0000); // Make file unreadable
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('File is not readable');
        
        $this->csvParser->parseFilePath($csvFile);
        
        // Clean up
        chmod($csvFile, 0644);
        unlink($csvFile);
    }

    /**
     * Create a CSV test file
     */
    private function createCsvFile(string $filename, array $lines): string
    {
        $filePath = $this->testFilesPath . '/' . $filename;
        file_put_contents($filePath, implode("\n", $lines));
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

