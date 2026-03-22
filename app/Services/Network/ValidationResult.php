<?php

namespace App\Services\Network;

class ValidationResult
{
    public bool $isValid;
    public array $errors;
    
    public function __construct(bool $isValid, array $errors = [])
    {
        $this->isValid = $isValid;
        $this->errors = $errors;
    }
    
    /**
     * Create a successful validation result
     * 
     * @return self
     */
    public static function success(): self
    {
        return new self(true, []);
    }
    
    /**
     * Create a failed validation result
     * 
     * @param array $errors Array of error messages
     * @return self
     */
    public static function failure(array $errors): self
    {
        return new self(false, $errors);
    }
    
    /**
     * Add an error to the validation result
     * 
     * @param string $error Error message
     * @return void
     */
    public function addError(string $error): void
    {
        $this->errors[] = $error;
        $this->isValid = false;
    }
    
    /**
     * Get all error messages as a single string
     * 
     * @return string
     */
    public function getErrorMessage(): string
    {
        return implode(', ', $this->errors);
    }
}
