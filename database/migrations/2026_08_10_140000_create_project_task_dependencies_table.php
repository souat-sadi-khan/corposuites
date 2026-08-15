<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_task_dependencies', function (Blueprint $table) {
            $table->id();

            // The task that must wait (the dependent).
            $table->foreignId('task_id')->constrained('project_tasks')->cascadeOnDelete();

            // The task that must be done first (the prerequisite).
            $table->foreignId('depends_on_task_id')->constrained('project_tasks')->cascadeOnDelete();

            // Standard FS / SS / FF / SF link types.
            $table->enum('dependency_type', ['finish_to_start', 'start_to_start', 'finish_to_finish', 'start_to_finish'])
                ->default('finish_to_start');

            // Positive = lag (gap after predecessor), negative = lead (overlap).
            $table->integer('lag_days')->nullable();

            $table->text('notes')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();

            // One dependency pair per direction — the same pair in the other
            // direction is a cycle and would be caught by cycle detection.
            $table->unique(['task_id', 'depends_on_task_id'], 'ptd_task_depends_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_task_dependencies');
    }
};
