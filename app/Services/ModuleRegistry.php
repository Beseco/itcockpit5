<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ModuleRegistry
{
    /**
     * Collection of registered modules
     *
     * @var Collection
     */
    private Collection $modules;

    /**
     * Create a new ModuleRegistry instance
     */
    public function __construct()
    {
        $this->modules = collect();
    }

    /**
     * Register a module with Laravel
     *
     * @param array $moduleMetadata Module metadata from module.json
     * @return void
     */
    public function register(array $moduleMetadata): void
    {
        // Validate required fields
        if (!isset($moduleMetadata['slug'])) {
            Log::error('Cannot register module without slug', ['metadata' => $moduleMetadata]);
            return;
        }

        $slug = $moduleMetadata['slug'];

        // Check if module is already registered
        if ($this->isModuleRegistered($slug)) {
            Log::warning('Module already registered', ['slug' => $slug]);
            return;
        }

        // Store module metadata in collection
        $this->modules->put($slug, $moduleMetadata);

        Log::info('Module registered successfully', [
            'slug' => $slug,
            'name' => $moduleMetadata['name'] ?? 'Unknown'
        ]);
    }

    /**
     * Get all registered modules
     *
     * @return Collection Collection of module metadata
     */
    public function getRegisteredModules(): Collection
    {
        return $this->modules;
    }

    /**
     * Find a module by its slug
     *
     * @param string $slug Module slug
     * @return array|null Module metadata or null if not found
     */
    public function getModuleBySlug(string $slug): ?array
    {
        return $this->modules->get($slug);
    }

    /**
     * Check if a module is registered
     *
     * @param string $slug Module slug
     * @return bool True if module is registered
     */
    public function isModuleRegistered(string $slug): bool
    {
        return $this->modules->has($slug);
    }
}
