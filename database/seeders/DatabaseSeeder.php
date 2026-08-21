<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(
            [
                LanguageSeeder::class,
                TranslationGroupSeeder::class,
                RolePermissionSeeder::class,
                HrmMenuSeeder::class,
                WorkflowMenuSeeder::class,
                LeaveWorkflowSeeder::class,
                CrmMenuSeeder::class,
                ProductMenuSeeder::class,
                SalesMenuSeeder::class,
                PurchaseMenuSeeder::class,
                InventoryMenuSeeder::class,
                AccountingMenuSeeder::class,
                AssetMenuSeeder::class,
                ProjectMenuSeeder::class,
                SupportMenuSeeder::class,
                BudgetMenuSeeder::class
            ]
        );
    }
}
