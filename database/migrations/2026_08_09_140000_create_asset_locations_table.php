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
        Schema::create('asset_locations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->enum('location_type', ['office', 'branch', 'warehouse', 'site', 'other'])->default('office');
            // References the existing HRM `departments` table per the
            // Naming/Table Conflict Guard — no second departments table.
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->string('building')->nullable();
            $table->string('floor')->nullable();
            $table->string('room')->nullable();
            $table->text('address')->nullable();
            $table->text('description')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::create('asset_location_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            // The destination must exist for the movement to mean anything,
            // so a location cannot be deleted out from under its history.
            $table->foreignId('asset_location_id')->constrained('asset_locations')->cascadeOnDelete();
            $table->date('moved_date');
            $table->foreignId('moved_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->text('reason')->nullable();
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
        Schema::dropIfExists('asset_location_movements');
        Schema::dropIfExists('asset_locations');
    }
};
