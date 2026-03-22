<?php

namespace App\Services;

use App\Models\Module;
use App\Models\User;
use Illuminate\Support\Collection;

class ModuleService
{
    /**
     * Aktiviert ein Modul
     *
     * @param Module $module
     * @return void
     */
    public function activateModule(Module $module): void
    {
        $module->update(['is_active' => true]);
    }

    /**
     * Deaktiviert ein Modul (außer Basismodul)
     *
     * @param Module $module
     * @return void
     * @throws \InvalidArgumentException wenn versucht wird, das Basismodul zu deaktivieren
     */
    public function deactivateModule(Module $module): void
    {
        if ($module->name === 'base') {
            throw new \InvalidArgumentException('Das Basismodul kann nicht deaktiviert werden.');
        }

        $module->update(['is_active' => false]);
    }

    /**
     * Registriert ein neues Modul zur Laufzeit
     *
     * @param string $name
     * @param string $displayName
     * @param string $description
     * @return Module
     */
    public function registerModule(string $name, string $displayName, string $description): Module
    {
        return Module::create([
            'name' => $name,
            'display_name' => $displayName,
            'description' => $description,
            'is_active' => true,
        ]);
    }

    /**
     * Gibt alle für einen Benutzer verfügbaren Module zurück
     *
     * @param User $user
     * @return Collection
     */
    public function getAvailableModulesForUser(User $user): Collection
    {
        // Superadministrator hat Zugriff auf alle Module
        if ($user->hasRole('Superadministrator')) {
            return Module::all();
        }

        // Hole alle aktiven Module
        $activeModules = Module::active()->get();

        // Filtere Module, für die der Benutzer mindestens eine Berechtigung hat
        return $activeModules->filter(function (Module $module) use ($user) {
            // Hole alle Berechtigungen des Moduls
            $modulePermissions = $module->permissions;

            // Prüfe, ob der Benutzer mindestens eine dieser Berechtigungen hat
            foreach ($modulePermissions as $permission) {
                if ($user->hasPermissionTo($permission->name)) {
                    return true;
                }
            }

            return false;
        });
    }
}
