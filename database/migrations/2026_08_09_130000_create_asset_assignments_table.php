<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('asset_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            // FK to the existing HRM `employees` table per the Naming/Table
            // Conflict Guard — no parallel staff table is created here.
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->date('assigned_date');
            $table->date('expected_return_date')->nullable();
            $table->date('returned_date')->nullable();
            $table->enum('assignment_status', ['assigned', 'returned', 'cancelled'])->default('assigned');
            $table->enum('condition_on_assign', ['new', 'good', 'fair', 'poor'])->default('good');
            $table->enum('condition_on_return', ['new', 'good', 'fair', 'poor'])->nullable();
            $table->text('notes')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_assignments');
    }
};
