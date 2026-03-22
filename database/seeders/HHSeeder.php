<?php

namespace Database\Seeders;

use App\Models\User;
use App\Modules\HH\Models\Account;
use App\Modules\HH\Models\BudgetPosition;
use App\Modules\HH\Models\BudgetYear;
use App\Modules\HH\Models\BudgetYearVersion;
use App\Modules\HH\Models\CostCenter;
use Illuminate\Database\Seeder;

class HHSeeder extends Seeder
{
    public function run(): void
    {
        $adminId = User::first()?->id ?? 1;

        // Cost Centers
        $it = CostCenter::firstOrCreate(['number' => '1000'], ['name' => 'IT-Infrastruktur', 'is_active' => true]);
        $netz = CostCenter::firstOrCreate(['number' => '1100'], ['name' => 'Netzwerk', 'is_active' => true]);
        $server = CostCenter::firstOrCreate(['number' => '1200'], ['name' => 'Server & Betrieb', 'is_active' => true]);

        // Accounts (Sachkonten)
        $hardware = Account::firstOrCreate(['number' => '68100'], ['name' => 'Hardware', 'type' => 'investiv', 'is_active' => true]);
        $software = Account::firstOrCreate(['number' => '68200'], ['name' => 'Software & Lizenzen', 'type' => 'konsumtiv', 'is_active' => true]);
        $dienstleistung = Account::firstOrCreate(['number' => '68300'], ['name' => 'Dienstleistungen', 'type' => 'konsumtiv', 'is_active' => true]);
        $wartung = Account::firstOrCreate(['number' => '68400'], ['name' => 'Wartung & Support', 'type' => 'konsumtiv', 'is_active' => true]);

        // Budget Year 2025 (active/planning)
        $year2025 = BudgetYear::firstOrCreate(
            ['year' => 2025],
            ['status' => 'preliminary', 'created_by' => $adminId]
        );

        $version2025 = BudgetYearVersion::firstOrCreate(
            ['budget_year_id' => $year2025->id, 'version_number' => 1],
            ['is_active' => true, 'created_by' => $adminId, 'created_at' => now()]
        );

        $positions2025 = [
            ['cost_center_id' => $it->id, 'account_id' => $hardware->id, 'project_name' => 'Laptop-Erneuerung', 'description' => 'Erneuerung von 20 Laptops', 'amount' => 30000.00, 'is_recurring' => false, 'priority' => 'hoch', 'category' => 'Pflichtaufgabe', 'status' => 'geplant'],
            ['cost_center_id' => $it->id, 'account_id' => $software->id, 'project_name' => 'Microsoft 365 Lizenzen', 'description' => 'Jahreslizenzen für 100 User', 'amount' => 15000.00, 'is_recurring' => true, 'priority' => 'hoch', 'category' => 'gesetzlich gebunden', 'status' => 'geplant'],
            ['cost_center_id' => $netz->id, 'account_id' => $hardware->id, 'project_name' => 'Switch-Erneuerung', 'description' => 'Erneuerung Core-Switches', 'amount' => 45000.00, 'is_recurring' => false, 'priority' => 'mittel', 'category' => 'Pflichtaufgabe', 'status' => 'geplant'],
            ['cost_center_id' => $netz->id, 'account_id' => $wartung->id, 'project_name' => 'Firewall-Wartung', 'description' => 'Jährlicher Wartungsvertrag', 'amount' => 8000.00, 'is_recurring' => true, 'priority' => 'hoch', 'category' => 'Pflichtaufgabe', 'status' => 'geplant'],
            ['cost_center_id' => $server->id, 'account_id' => $hardware->id, 'project_name' => 'Server-Erweiterung', 'description' => 'RAM-Erweiterung Produktivserver', 'amount' => 12000.00, 'is_recurring' => false, 'priority' => 'mittel', 'category' => 'freiwillige Leistung', 'status' => 'geplant'],
            ['cost_center_id' => $server->id, 'account_id' => $dienstleistung->id, 'project_name' => 'Cloud-Backup', 'description' => 'Backup-as-a-Service Jahresvertrag', 'amount' => 6000.00, 'is_recurring' => true, 'priority' => 'hoch', 'category' => 'Pflichtaufgabe', 'status' => 'geplant'],
        ];

        foreach ($positions2025 as $pos) {
            BudgetPosition::firstOrCreate(
                ['budget_year_version_id' => $version2025->id, 'project_name' => $pos['project_name']],
                array_merge($pos, ['budget_year_version_id' => $version2025->id, 'start_year' => 2025, 'created_by' => $adminId])
            );
        }

        // Budget Year 2026 (draft)
        $year2026 = BudgetYear::firstOrCreate(
            ['year' => 2026],
            ['status' => 'draft', 'created_by' => $adminId]
        );

        $version2026 = BudgetYearVersion::firstOrCreate(
            ['budget_year_id' => $year2026->id, 'version_number' => 1],
            ['is_active' => true, 'created_by' => $adminId, 'created_at' => now()]
        );

        BudgetPosition::firstOrCreate(
            ['budget_year_version_id' => $version2026->id, 'project_name' => 'Microsoft 365 Lizenzen'],
            ['cost_center_id' => $it->id, 'account_id' => $software->id, 'description' => 'Jahreslizenzen für 100 User', 'amount' => 15600.00, 'is_recurring' => true, 'priority' => 'hoch', 'category' => 'gesetzlich gebunden', 'status' => 'geplant', 'start_year' => 2026, 'created_by' => $adminId]
        );

        BudgetPosition::firstOrCreate(
            ['budget_year_version_id' => $version2026->id, 'project_name' => 'Firewall-Wartung'],
            ['cost_center_id' => $netz->id, 'account_id' => $wartung->id, 'description' => 'Jährlicher Wartungsvertrag', 'amount' => 8400.00, 'is_recurring' => true, 'priority' => 'hoch', 'category' => 'Pflichtaufgabe', 'status' => 'geplant', 'start_year' => 2026, 'created_by' => $adminId]
        );
    }
}
