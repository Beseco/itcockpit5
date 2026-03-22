<?php

namespace App\Services\Network;

use App\Exceptions\CriticalImportException;
use App\Exceptions\ParseException;
use Illuminate\Support\Facades\Log;

class SqlFileParser
{
    /**
     * Parse SQL dump file and extract INSERT statements
     * 
     * @param string $filePath Path to SQL dump file
     * @return array Array of parsed records
     * @throws CriticalImportException
     */
    public function parse(string $filePath): array
    {
        Log::info("Starting SQL file parsing", ['file' => $filePath]);

        // Critical error: File not found
        if (!file_exists($filePath)) {
            Log::error("SQL file not found", ['file' => $filePath]);
            throw CriticalImportException::fileNotFound($filePath);
        }

        // Critical error: Cannot read file
        $content = file_get_contents($filePath);
        if ($content === false) {
            Log::error("Failed to read SQL file", ['file' => $filePath]);
            throw CriticalImportException::severeParsingError("Cannot read file: {$filePath}");
        }

        // Extract all INSERT statements
        try {
            $insertStatements = $this->extractInsertStatements($content);
        } catch (ParseException $e) {
            Log::error("Failed to extract INSERT statements", [
                'file' => $filePath,
                'error' => $e->getMessage()
            ]);
            throw CriticalImportException::severeParsingError($e->getMessage());
        }
        
        if (empty($insertStatements)) {
            Log::warning("No INSERT statements found in SQL file", ['file' => $filePath]);
            return [];
        }

        // Parse each INSERT statement to extract records
        $records = [];
        $parseErrors = 0;
        
        foreach ($insertStatements as $index => $statement) {
            try {
                $extractedRecords = $this->extractValues($statement);
                $records = array_merge($records, $extractedRecords);
                
                Log::debug("Successfully parsed INSERT statement", [
                    'statement_index' => $index,
                    'records_extracted' => count($extractedRecords)
                ]);
                
            } catch (ParseException $e) {
                // Non-critical: Log and continue with other statements
                $parseErrors++;
                Log::warning("Failed to parse INSERT statement (non-critical)", [
                    'statement_index' => $index,
                    'error' => $e->getMessage()
                ]);
            }
        }

        Log::info("SQL file parsing completed", [
            'file' => $filePath,
            'total_records' => count($records),
            'parse_errors' => $parseErrors
        ]);

        return $records;
    }

    /**
     * Extract INSERT statements from SQL content
     * 
     * @param string $content SQL file content
     * @return array Array of INSERT statement strings
     */
    private function extractInsertStatements(string $content): array
    {
        $statements = [];
        
        // Match ALL INSERT INTO statements (can span multiple lines)
        // phpMyAdmin often splits large inserts into multiple INSERT statements
        $pattern = '/INSERT\s+INTO\s+`([^`]+)`\s*\([^)]+\)\s+VALUES\s+/is';
        
        // Use preg_match_all to find ALL INSERT statements, not just the first one
        if (preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[0] as $match) {
                $startPos = $match[1] + strlen($match[0]);
                $valuesSection = $this->extractValuesSection(substr($content, $startPos));
                
                if ($valuesSection !== null) {
                    $statements[] = $valuesSection;
                }
            }
        }
        
        return $statements;
    }
    
    /**
     * Extract the VALUES section from content starting at VALUES
     * 
     * @param string $content Content starting after "VALUES "
     * @return string|null The VALUES section or null if not found
     */
    private function extractValuesSection(string $content): ?string
    {
        $result = '';
        $inString = false;
        $stringDelimiter = null;
        $escaped = false;
        $parenDepth = 0;
        $length = strlen($content);
        
        for ($i = 0; $i < $length; $i++) {
            $char = $content[$i];
            
            // Handle escape sequences
            if ($escaped) {
                $result .= $char;
                $escaped = false;
                continue;
            }
            
            if ($char === '\\') {
                $result .= $char;
                $escaped = true;
                continue;
            }
            
            // Handle string delimiters
            if (($char === "'" || $char === '"') && !$inString) {
                $inString = true;
                $stringDelimiter = $char;
                $result .= $char;
                continue;
            }
            
            if ($char === $stringDelimiter && $inString) {
                $inString = false;
                $stringDelimiter = null;
                $result .= $char;
                continue;
            }
            
            // If we're in a string, just add the character
            if ($inString) {
                $result .= $char;
                continue;
            }
            
            // Track parentheses
            if ($char === '(') {
                $parenDepth++;
                $result .= $char;
                continue;
            }
            
            if ($char === ')') {
                $parenDepth--;
                $result .= $char;
                
                // If we hit depth 0 and the next char is semicolon, we're done
                if ($parenDepth === 0 && $i + 1 < $length && $content[$i + 1] === ';') {
                    return $result;
                }
                continue;
            }
            
            $result .= $char;
        }
        
        return null;
    }

    /**
     * Extract values from INSERT statement
     * 
     * @param string $valuesSection The VALUES section of INSERT statement
     * @return array Array of value arrays
     * @throws ParseException
     */
    private function extractValues(string $valuesSection): array
    {
        $records = [];
        $valuesSection = trim($valuesSection);
        
        if (empty($valuesSection)) {
            throw ParseException::malformedInsertStatement("Empty VALUES section");
        }
        
        // Split by row boundaries: ),( or ),\n(
        // We need to be careful not to split on commas inside strings
        try {
            $rows = $this->splitIntoRows($valuesSection);
        } catch (\Exception $e) {
            throw ParseException::malformedInsertStatement("Failed to split rows: " . $e->getMessage());
        }
        
        if (empty($rows)) {
            throw ParseException::malformedInsertStatement("No rows found in VALUES section");
        }
        
        $rowErrors = 0;
        foreach ($rows as $rowIndex => $row) {
            try {
                $parsedRow = $this->parseRow($row);
                $records[] = $parsedRow;
            } catch (ParseException $e) {
                // Non-critical: Log and continue with other rows
                $rowErrors++;
                Log::warning("Failed to parse row (non-critical)", [
                    'row_index' => $rowIndex,
                    'error' => $e->getMessage(),
                    'row_preview' => substr($row, 0, 100)
                ]);
            }
        }
        
        // If all rows failed, this is a critical parsing error
        if (empty($records) && $rowErrors > 0) {
            throw ParseException::malformedInsertStatement("All rows failed to parse");
        }
        
        return $records;
    }

    /**
     * Split VALUES section into individual rows
     * 
     * @param string $valuesSection VALUES section content
     * @return array Array of row strings
     */
    private function splitIntoRows(string $valuesSection): array
    {
        $rows = [];
        $currentRow = '';
        $inString = false;
        $stringDelimiter = null;
        $escaped = false;
        $parenDepth = 0;
        $rowStarted = false;
        
        $length = strlen($valuesSection);
        
        for ($i = 0; $i < $length; $i++) {
            $char = $valuesSection[$i];
            
            // Handle escape sequences
            if ($escaped) {
                $currentRow .= $char;
                $escaped = false;
                continue;
            }
            
            if ($char === '\\') {
                $currentRow .= $char;
                $escaped = true;
                continue;
            }
            
            // Handle string delimiters
            if (($char === "'" || $char === '"') && !$inString) {
                $inString = true;
                $stringDelimiter = $char;
                $currentRow .= $char;
                continue;
            }
            
            if ($char === $stringDelimiter && $inString) {
                $inString = false;
                $stringDelimiter = null;
                $currentRow .= $char;
                continue;
            }
            
            // If we're in a string, just add the character
            if ($inString) {
                $currentRow .= $char;
                continue;
            }
            
            // Track parentheses depth
            if ($char === '(') {
                if ($parenDepth === 0) {
                    $rowStarted = true;
                }
                $parenDepth++;
                $currentRow .= $char;
                continue;
            }
            
            if ($char === ')') {
                $currentRow .= $char;
                $parenDepth--;
                
                // If we're back to depth 0 and we had started a row, we've completed it
                if ($parenDepth === 0 && $rowStarted) {
                    $rows[] = trim($currentRow);
                    $currentRow = '';
                    $rowStarted = false;
                    
                    // Skip the comma and any whitespace after the closing paren
                    while ($i + 1 < $length && in_array($valuesSection[$i + 1], [',', ' ', "\n", "\r", "\t"])) {
                        $i++;
                    }
                }
                continue;
            }
            
            // Only add characters if we're inside a row
            if ($rowStarted) {
                $currentRow .= $char;
            }
        }
        
        // Add any remaining row
        if (!empty(trim($currentRow))) {
            $rows[] = trim($currentRow);
        }
        
        return $rows;
    }

    /**
     * Parse a single row of values
     * 
     * @param string $row Row string with comma-separated values
     * @return array Array of parsed values
     * @throws ParseException
     */
    private function parseRow(string $row): array
    {
        // Remove surrounding parentheses
        $row = trim($row);
        if (substr($row, 0, 1) === '(') {
            $row = substr($row, 1);
        }
        if (substr($row, -1) === ')') {
            $row = substr($row, 0, -1);
        }
        
        if (empty($row)) {
            throw ParseException::invalidRowFormat("Empty row after removing parentheses");
        }
        
        $values = [];
        $currentValue = '';
        $inString = false;
        $stringDelimiter = null;
        $escaped = false;
        
        $length = strlen($row);
        
        for ($i = 0; $i < $length; $i++) {
            $char = $row[$i];
            
            // Handle escape sequences
            if ($escaped) {
                $currentValue .= $char;
                $escaped = false;
                continue;
            }
            
            if ($char === '\\') {
                $escaped = true;
                continue;
            }
            
            // Handle string delimiters
            if (($char === "'" || $char === '"') && !$inString) {
                $inString = true;
                $stringDelimiter = $char;
                continue;
            }
            
            if ($char === $stringDelimiter && $inString) {
                $inString = false;
                $stringDelimiter = null;
                continue;
            }
            
            // If we're in a string, add the character
            if ($inString) {
                $currentValue .= $char;
                continue;
            }
            
            // Comma outside of string means end of value
            if ($char === ',') {
                $values[] = $this->normalizeValue(trim($currentValue));
                $currentValue = '';
                continue;
            }
            
            $currentValue .= $char;
        }
        
        // Add the last value
        if ($currentValue !== '' || count($values) > 0) {
            $values[] = $this->normalizeValue(trim($currentValue));
        }
        
        if (empty($values)) {
            throw ParseException::invalidRowFormat("No values extracted from row");
        }
        
        return $values;
    }

    /**
     * Normalize a value (handle NULL, empty strings, etc.)
     * 
     * @param string $value Raw value from SQL
     * @return mixed Normalized value
     */
    private function normalizeValue(string $value): mixed
    {
        // Handle NULL
        if (strtoupper($value) === 'NULL' || $value === '') {
            return null;
        }
        
        // Remove quotes if present
        if ((substr($value, 0, 1) === "'" && substr($value, -1) === "'") ||
            (substr($value, 0, 1) === '"' && substr($value, -1) === '"')) {
            $value = substr($value, 1, -1);
        }
        
        // Unescape escaped characters
        $value = str_replace("\\'", "'", $value);
        $value = str_replace('\\"', '"', $value);
        $value = str_replace('\\\\', '\\', $value);
        
        return $value;
    }
}
