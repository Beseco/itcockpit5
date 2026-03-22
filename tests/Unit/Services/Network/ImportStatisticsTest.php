<?php

namespace Tests\Unit\Services\Network;

use App\Services\Network\ImportStatistics;
use Tests\TestCase;

class ImportStatisticsTest extends TestCase
{
    public function test_it_initializes_with_zero_values(): void
    {
        $stats = new ImportStatistics();

        $this->assertEquals(0, $stats->totalProcessed);
        $this->assertEquals(0, $stats->successfullyImported);
        $this->assertEquals(0, $stats->skippedDuplicates);
        $this->assertEquals(0, $stats->validationErrors);
        $this->assertEmpty($stats->errorMessages);
    }

    public function test_it_adds_success(): void
    {
        $stats = new ImportStatistics();
        $stats->addSuccess();

        $this->assertEquals(1, $stats->totalProcessed);
        $this->assertEquals(1, $stats->successfullyImported);
        $this->assertEquals(0, $stats->skippedDuplicates);
        $this->assertEquals(0, $stats->validationErrors);
    }

    public function test_it_adds_duplicate(): void
    {
        $stats = new ImportStatistics();
        $stats->addDuplicate();

        $this->assertEquals(1, $stats->totalProcessed);
        $this->assertEquals(0, $stats->successfullyImported);
        $this->assertEquals(1, $stats->skippedDuplicates);
        $this->assertEquals(0, $stats->validationErrors);
    }

    public function test_it_adds_error(): void
    {
        $stats = new ImportStatistics();
        $stats->addError('Test error message');

        $this->assertEquals(1, $stats->totalProcessed);
        $this->assertEquals(0, $stats->successfullyImported);
        $this->assertEquals(0, $stats->skippedDuplicates);
        $this->assertEquals(1, $stats->validationErrors);
        $this->assertCount(1, $stats->errorMessages);
        $this->assertEquals('Test error message', $stats->errorMessages[0]);
    }

    public function test_it_tracks_multiple_operations(): void
    {
        $stats = new ImportStatistics();
        $stats->addSuccess();
        $stats->addSuccess();
        $stats->addDuplicate();
        $stats->addError('Error 1');
        $stats->addError('Error 2');

        $this->assertEquals(5, $stats->totalProcessed);
        $this->assertEquals(2, $stats->successfullyImported);
        $this->assertEquals(1, $stats->skippedDuplicates);
        $this->assertEquals(2, $stats->validationErrors);
        $this->assertCount(2, $stats->errorMessages);
    }

    public function test_it_generates_summary_without_errors(): void
    {
        $stats = new ImportStatistics();
        $stats->addSuccess();
        $stats->addSuccess();
        $stats->addDuplicate();

        $summary = $stats->getSummary();

        $this->assertStringContainsString('Total Processed: 3', $summary);
        $this->assertStringContainsString('Successfully Imported: 2', $summary);
        $this->assertStringContainsString('Skipped Duplicates: 1', $summary);
        $this->assertStringContainsString('Validation Errors: 0', $summary);
        $this->assertStringNotContainsString('Error Messages:', $summary);
    }

    public function test_it_generates_summary_with_errors(): void
    {
        $stats = new ImportStatistics();
        $stats->addSuccess();
        $stats->addError('Error 1');
        $stats->addError('Error 2');

        $summary = $stats->getSummary();

        $this->assertStringContainsString('Total Processed: 3', $summary);
        $this->assertStringContainsString('Successfully Imported: 1', $summary);
        $this->assertStringContainsString('Validation Errors: 2', $summary);
        $this->assertStringContainsString('Error Messages:', $summary);
        $this->assertStringContainsString('1. Error 1', $summary);
        $this->assertStringContainsString('2. Error 2', $summary);
    }
}
