<?php

namespace App\Services\Network;

class ImportStatistics
{
    public int $totalProcessed = 0;
    public int $successfullyImported = 0;
    public int $skippedDuplicates = 0;
    public int $validationErrors = 0;
    public array $errorMessages = [];

    /**
     * Add a successful import
     */
    public function addSuccess(): void
    {
        $this->totalProcessed++;
        $this->successfullyImported++;
    }

    /**
     * Add a duplicate record
     */
    public function addDuplicate(): void
    {
        $this->totalProcessed++;
        $this->skippedDuplicates++;
    }

    /**
     * Add an error
     *
     * @param string $message Error message
     */
    public function addError(string $message): void
    {
        $this->totalProcessed++;
        $this->validationErrors++;
        $this->errorMessages[] = $message;
    }

    /**
     * Get formatted summary of import statistics
     *
     * @return string Formatted summary
     */
    public function getSummary(): string
    {
        $summary = "Import Statistics:\n";
        $summary .= "==================\n";
        $summary .= "Total Processed: {$this->totalProcessed}\n";
        $summary .= "Successfully Imported: {$this->successfullyImported}\n";
        $summary .= "Skipped Duplicates: {$this->skippedDuplicates}\n";
        $summary .= "Validation Errors: {$this->validationErrors}\n";

        if (!empty($this->errorMessages)) {
            $summary .= "\nError Messages:\n";
            foreach ($this->errorMessages as $index => $error) {
                $summary .= "  " . ($index + 1) . ". {$error}\n";
            }
        }

        return $summary;
    }
}
