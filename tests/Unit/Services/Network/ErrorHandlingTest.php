<?php

namespace Tests\Unit\Services\Network;

use App\Exceptions\CriticalImportException;
use App\Exceptions\ParseException;
use Tests\TestCase;

class ErrorHandlingTest extends TestCase
{
    /** @test */
    public function critical_import_exception_can_be_created_for_file_not_found()
    {
        $exception = CriticalImportException::fileNotFound('/path/to/missing.sql');
        
        $this->assertInstanceOf(CriticalImportException::class, $exception);
        $this->assertStringContainsString('SQL file not found', $exception->getMessage());
        $this->assertStringContainsString('/path/to/missing.sql', $exception->getMessage());
    }

    /** @test */
    public function critical_import_exception_can_be_created_for_database_connection_failure()
    {
        $exception = CriticalImportException::databaseConnectionFailed('Connection timeout');
        
        $this->assertInstanceOf(CriticalImportException::class, $exception);
        $this->assertStringContainsString('Database connection failed', $exception->getMessage());
        $this->assertStringContainsString('Connection timeout', $exception->getMessage());
    }

    /** @test */
    public function critical_import_exception_can_be_created_for_severe_parsing_error()
    {
        $exception = CriticalImportException::severeParsingError('Invalid SQL syntax');
        
        $this->assertInstanceOf(CriticalImportException::class, $exception);
        $this->assertStringContainsString('Severe SQL parsing error', $exception->getMessage());
        $this->assertStringContainsString('Invalid SQL syntax', $exception->getMessage());
    }

    /** @test */
    public function critical_import_exception_can_be_created_for_insufficient_permissions()
    {
        $exception = CriticalImportException::insufficientPermissions('Cannot write to database');
        
        $this->assertInstanceOf(CriticalImportException::class, $exception);
        $this->assertStringContainsString('Insufficient database permissions', $exception->getMessage());
        $this->assertStringContainsString('Cannot write to database', $exception->getMessage());
    }

    /** @test */
    public function parse_exception_can_be_created_for_invalid_sql_syntax()
    {
        $exception = ParseException::invalidSqlSyntax('Missing closing parenthesis');
        
        $this->assertInstanceOf(ParseException::class, $exception);
        $this->assertStringContainsString('Invalid SQL syntax', $exception->getMessage());
        $this->assertStringContainsString('Missing closing parenthesis', $exception->getMessage());
    }

    /** @test */
    public function parse_exception_can_be_created_for_malformed_insert_statement()
    {
        $exception = ParseException::malformedInsertStatement('Empty VALUES section');
        
        $this->assertInstanceOf(ParseException::class, $exception);
        $this->assertStringContainsString('Malformed INSERT statement', $exception->getMessage());
        $this->assertStringContainsString('Empty VALUES section', $exception->getMessage());
    }

    /** @test */
    public function parse_exception_can_be_created_for_invalid_row_format()
    {
        $exception = ParseException::invalidRowFormat('No values extracted');
        
        $this->assertInstanceOf(ParseException::class, $exception);
        $this->assertStringContainsString('Invalid row format', $exception->getMessage());
        $this->assertStringContainsString('No values extracted', $exception->getMessage());
    }

    /** @test */
    public function parse_exception_can_be_created_for_unexpected_data_structure()
    {
        $exception = ParseException::unexpectedDataStructure('Expected 11 columns, got 5');
        
        $this->assertInstanceOf(ParseException::class, $exception);
        $this->assertStringContainsString('Unexpected data structure', $exception->getMessage());
        $this->assertStringContainsString('Expected 11 columns', $exception->getMessage());
    }

    /** @test */
    public function critical_import_exception_extends_exception()
    {
        $exception = new CriticalImportException('Test message');
        
        $this->assertInstanceOf(\Exception::class, $exception);
    }

    /** @test */
    public function parse_exception_extends_exception()
    {
        $exception = new ParseException('Test message');
        
        $this->assertInstanceOf(\Exception::class, $exception);
    }

    /** @test */
    public function exceptions_can_be_created_with_previous_exception()
    {
        $previous = new \Exception('Original error');
        $exception = new CriticalImportException('Wrapped error', 0, $previous);
        
        $this->assertSame($previous, $exception->getPrevious());
    }

    /** @test */
    public function exceptions_can_be_created_with_error_code()
    {
        $exception = new CriticalImportException('Test message', 500);
        
        $this->assertEquals(500, $exception->getCode());
    }
}
