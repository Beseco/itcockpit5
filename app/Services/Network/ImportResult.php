<?php

namespace App\Services\Network;

class ImportResult
{
    public bool $success;
    public bool $isDuplicate;
    public ?string $errorMessage;
    public ?int $recordId;

    public function __construct(
        bool $success,
        bool $isDuplicate = false,
        ?string $errorMessage = null,
        ?int $recordId = null
    ) {
        $this->success = $success;
        $this->isDuplicate = $isDuplicate;
        $this->errorMessage = $errorMessage;
        $this->recordId = $recordId;
    }

    /**
     * Create a successful import result
     *
     * @param int $recordId The ID of the imported record
     * @return self
     */
    public static function success(int $recordId): self
    {
        return new self(true, false, null, $recordId);
    }

    /**
     * Create a duplicate import result
     *
     * @return self
     */
    public static function duplicate(): self
    {
        return new self(true, true, null, null);
    }

    /**
     * Create a failed import result
     *
     * @param string $errorMessage Error message
     * @return self
     */
    public static function failure(string $errorMessage): self
    {
        return new self(false, false, $errorMessage, null);
    }
}
