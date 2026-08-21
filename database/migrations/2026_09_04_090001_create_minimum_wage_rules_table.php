<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('minimum_wage_rules', function (Blueprint $table) {
            $table->id();
            $table->string('country');
            // Nullable state = a country-wide fallback rule; a specific
            // state row overrides it for that state only, the same
            // "more specific scope wins" convention Reorder Level already
            // established for product/warehouse.
            $table->string('state')->nullable();
            // Only the two pay types Salary Structure/Template actually use
            // a fixed rate for — commission-based pay has no floor to check
            // against, so it's deliberately excluded from this enum.
            $table->enum('wage_type', ['daily', 'monthly'])->default('monthly');
            $table->decimal('minimum_wage', 10, 2)->default(0);
            // Minimum wage law changes over time; the "currently applicable"
            // rule for a given country/state/wage_type is the one with the
            // latest effective_date not in the future — the exact same
            // "orderByDesc('effective_date')->first()" resolution Salary
            // Structure already uses for its own active-structure lookup.
            $table->date('effective_date');
            $table->text('description')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->unique(
                ['country', 'state', 'wage_type', 'effective_date'],
                'min_wage_rules_scope_date_unique'
            );
        });
    }

    public function down()
    {
        Schema::dropIfExists('minimum_wage_rules');
    }
};
