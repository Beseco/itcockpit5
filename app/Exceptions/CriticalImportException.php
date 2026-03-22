<?php

namespace App\Exceptions;

use Exception;

/**
 * Critical Import Exception
 * 
 * Thrown when a critical error occurs during import that requires
 * the entire import process to be aborted and rolled back.
 * 
 * Examples:
 * - Database connection lost
 * - SQL file not found
 * - Severe parsing errors
 * - Insufficient database permissions
 */
class CriticalImportException extends Exception
{
    /**
     * Create a new critical import exception
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
     * Create exception for missing file
     */
    public static function fileNotFound(string $filePath): self
    {
        return new self("Critical error: SQL file not found at path: {$filePath}");
    }

    /**
     * Create exception for database connection failure
     */
    public static function databaseConnectionFailed(string $message): self
    {
        return new self("Critical error: Database connection failed - {$message}");
    }

    /**
     * Create exception for severe parsing error
     */
    public static function severeParsingError(string $message): self
    {
        return new self("Critical error: Severe SQL parsing error - {$message}");
    }

    /**
     * Create exception for insufficient permissions
     */
    public static function insufficientPermissions(string $message): self
    {
        return new self("Critical error: Insufficient database permissions - {$message}");
    }
}
