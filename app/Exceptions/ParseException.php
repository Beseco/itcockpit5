<?php

namespace App\Exceptions;

use Exception;

/**
 * Parse Exception
 * 
 * Thrown when a parsing error occurs that prevents proper extraction
 * of data from SQL files. This is typically a non-critical error that
 * allows the import to continue with other records.
 */
class ParseException extends Exception
{
    /**
     * Create a new parse exception
     * 
     * @param string $message Error message
     * @param int $code Error code
     * @param \Throwable|null $previous Previous exception
     */
    public function __construct(string $message = "", int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Create exception for invalid SQL syntax
     */
    public static function invalidSqlSyntax(string $details): self
    {
        return new self("Parse error: Invalid SQL syntax - {$details}");
    }

    /**
     * Create exception for malformed INSERT statement
     */
    public static function malformedInsertStatement(string $details): self
    {
        return new self("Parse error: Malformed INSERT statement - {$details}");
    }

    /**
     * Create exception for invalid row format
     */
    public static function invalidRowFormat(string $details): self
    {
        return new self("Parse error: Invalid row format - {$details}");
    }

    /**
     * Create exception for unexpected data structure
     */
    public static function unexpectedDataStructure(string $details): self
    {
        return new self("Parse error: Unexpected data structure - {$details}");
    }
}
