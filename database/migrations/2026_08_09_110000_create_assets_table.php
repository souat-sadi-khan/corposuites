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
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('asset_code')->unique();
            $table->string('name');
            // Nullable at the DB level with nullOnDelete even though the Form
            // Request requires it: deleting a category must never destroy the
            // physical asset records filed under it — an orphaned asset is
            // recoverable, a cascade-deleted register is not.
            $table->foreignId('asset_category_id')->nullable()->constrained('asset_categories')->nullOnDelete();
            $table->string('serial_number')->nullable()->unique();
            $table->string('model_number')->nullable();
            $table->string('manufacturer')->nullable();
            $table->enum('condition', ['new', 'good', 'fair', 'poor'])->default('good');
            $table->enum('asset_status', ['in_store', 'in_use', 'under_maintenance', 'disposed'])->default('in_store');
            $table->text('description')->nullable();
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
        Schema::dropIfExists('assets');
    }
};
