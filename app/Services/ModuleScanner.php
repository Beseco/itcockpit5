<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class ModuleScanner
{
    /**
     * Scan the /app/Modules/ directory for valid modules
     *
     * @return array Array of valid module metadata
     */
    public function scan(): array
    {
        $modulesPath = app_path('Modules');
        $validModules = [];

        // Check if Modules directory exists
        if (!File::isDirectory($modulesPath)) {
            Log::warning('Modules directory does not exist', ['path' => $modulesPath]);
            return [];
        }

        // Get all subdirectories in the Modules folder
        $directories = File::directories($modulesPath);

        foreach ($directories as $directory) {
            $moduleName = basename($directory);
            
            if ($this->validateModule($directory)) {
                $metadata = $this->loadModuleMetadata($directory);
                if ($metadata) {
                    $validModules[] = $metadata;
                    Log::info('Module loaded successfully', ['module' => $moduleName]);
                }
            }
        }

        return $validModules;
    }

    /**
     * Validate that a module has all required files and structure
     *
     * @param string $path Path to the module directory
     * @return bool True if module is valid
     */
    public function validateModule(string $path): bool
    {
        $moduleName = basename($path);

        // Check for module.json
        $moduleJsonPath = $path . '/module.json';
        if (!File::exists($moduleJsonPath)) {
            Log::error('Module missing module.json', ['module' => $moduleName, 'path' => $path]);
            return false;
        }

        // Validate module.json content
        $metadata = $this->loadModuleMetadata($path);
        if (!$metadata) {
            return false;
        }

        // Check for required fields in module.json
        $requiredFields = ['name', 'slug', 'version'];
        foreach ($requiredFields as $field) {
            if (!isset($metadata[$field]) || empty($metadata[$field])) {
                Log::error("Module missing required field: {$field}", ['module' => $moduleName]);
                return false;
            }
        }

        // Check for ServiceProvider class
        $serviceProviderPath = $path . '/Providers/' . $moduleName . 'ServiceProvider.php';
        if (!File::exists($serviceProviderPath)) {
            Log::error('Module ServiceProvider not found', [
                'module' => $moduleName,
                'expected_path' => $serviceProviderPath
            ]);
            return false;
        }

        return true;
    }

    /**
     * Load and parse module.json metadata
     *
     * @param string $path Path to the module directory
     * @return array|null Module metadata or null if invalid
     */
    private function loadModuleMetadata(string $path): ?array
    {
        $moduleName = basename($path);
        $moduleJsonPath = $path . '/module.json';

        try {
            $jsonContent = File::get($moduleJsonPath);
            $metadata = json_decode($jsonContent, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('Module has invalid JSON in module.json', [
                    'module' => $moduleName,
                    'error' => json_last_error_msg()
                ]);
                return null;
            }

            return $metadata;
        } catch (\Exception $e) {
            Log::error('Failed to read module.json', [
                'module' => $moduleName,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}
