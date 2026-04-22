<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class HookManager
{
    /**
     * Collection of registered sidebar items
     *
     * @var Collection
     */
    private Collection $sidebarItems;

    /**
     * Collection of registered dashboard widgets
     *
     * @var Collection
     */
    private Collection $dashboardWidgets;

    /**
     * Collection of registered permissions
     *
     * @var Collection
     */
    private Collection $permissions;

    /**
     * Create a new HookManager instance
     */
    public function __construct()
    {
        $this->sidebarItems = collect();
        $this->dashboardWidgets = collect();
        $this->permissions = collect();
    }

    /**
     * Register a sidebar navigation item for a module
     *
     * @param string $module Module slug
     * @param array $item Sidebar item data ['label', 'route', 'icon', 'permission']
     * @return void
     */
    public function registerSidebarItem(string $module, array $item): void
    {
        // Validate required fields
        if (!isset($item['label']) || !isset($item['route'])) {
            Log::error('Sidebar item missing required fields', [
                'module' => $module,
                'item' => $item
            ]);
            return;
        }

        // Add module context to the item (preserve explicit 'module' key if already set)
        $item['module'] = $item['module'] ?? $module;

        // Store sidebar item
        $this->sidebarItems->push($item);

        Log::info('Sidebar item registered', [
            'module' => $module,
            'label' => $item['label']
        ]);
    }

    /**
     * Get sidebar items filtered by user permissions and module status
     *
     * @param User $user User to filter items for
     * @return Collection Collection of sidebar items the user can access
     */
    public function getSidebarItems(User $user): Collection
    {
        return $this->sidebarItems->filter(function ($item) use ($user) {
            // Super admins can see all items
            if ($user->isSuperAdmin()) {
                return true;
            }

            // Check if the module is active (if module is specified)
            if (isset($item['module'])) {
                $module = \App\Models\Module::where('name', $item['module'])->first();

                // If module exists and is inactive, hide the item
                if ($module && !$module->isActive()) {
                    return false;
                }
            }

            // If item has a permission requirement, check it
            if (isset($item['permission'])) {
                return $user->hasModulePermission(...explode('.', $item['permission'], 2));
            }

            // If no permission specified, check default module.{slug}.view permission
            if (isset($item['module'])) {
                return $user->hasModulePermission($item['module'], 'view');
            }

            // Default: don't show if we can't determine permissions
            return false;
        });
    }


    /**
     * Register a dashboard widget for a module
     *
     * @param string $module Module slug
     * @param string $viewPath Blade view path for the widget
     * @return void
     */
    public function registerDashboardWidget(string $module, string $viewPath): void
    {
        if (empty($viewPath)) {
            Log::error('Dashboard widget missing view path', ['module' => $module]);
            return;
        }

        // Store widget with module context
        $this->dashboardWidgets->push([
            'module' => $module,
            'viewPath' => $viewPath
        ]);

        Log::info('Dashboard widget registered', [
            'module' => $module,
            'viewPath' => $viewPath
        ]);
    }

    /**
     * Get dashboard widgets filtered by user permissions
     *
     * @param User $user User to filter widgets for
     * @return Collection Collection of widget view paths the user can access
     */
    public function getDashboardWidgets(User $user): Collection
    {
        return $this->dashboardWidgets->filter(function ($widget) use ($user) {
            // Super admins can see all widgets
            if ($user->isSuperAdmin()) {
                return true;
            }

            // Check if user has view permission for the module
            if (isset($widget['module'])) {
                return $user->hasModulePermission($widget['module'], 'view');
            }

            // Default: don't show if we can't determine permissions
            return false;
        });
    }

    /**
     * Register a custom permission for a module
     *
     * @param string $module Module slug
     * @param string $permission Permission name (e.g., 'view', 'edit')
     * @param string $description Human-readable description of the permission
     * @return void
     */
    public function registerPermission(string $module, string $permission, string $description): void
    {
        if (empty($module) || empty($permission)) {
            Log::error('Permission registration missing required fields', [
                'module' => $module,
                'permission' => $permission
            ]);
            return;
        }

        // Format permission as module.{slug}.{permission}
        $permissionName = "module.{$module}.{$permission}";

        // Store permission metadata
        $this->permissions->push([
            'name' => $permissionName,
            'module' => $module,
            'permission' => $permission,
            'description' => $description
        ]);

        Log::info('Permission registered', [
            'module' => $module,
            'permission' => $permissionName,
            'description' => $description
        ]);
    }

    /**
     * Get all registered permissions
     *
     * @return Collection Collection of registered permissions
     */
    public function getRegisteredPermissions(): Collection
    {
        return $this->permissions;
    }
}
