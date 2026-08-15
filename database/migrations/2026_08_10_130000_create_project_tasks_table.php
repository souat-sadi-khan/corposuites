<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The unit of work on a project.
     *
     * Table name is `project_tasks`, not `tasks` — a bare `tasks` table is the
     * kind of generic name a later module (Support tickets, workflow to-dos)
     * would plausibly want, so this one is namespaced to its module per the
     * Naming/Table Conflict Guard's spirit.
     *
     * Deliberately carries no dependency link and no logged time: "Task
     * Dependencies" and "Time Tracking" are their own checklist items and will
     * attach through task_id. `sort_order` is the position within a board
     * column, which "Task Board Kanban" will reorder.
     */
    public function up(): void
    {
        Schema::create('project_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('task_code')->unique(); // server-generated, immutable
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('project_milestone_id')->nullable()->constrained('project_milestones')->nullOnDelete();
            $table->string('title');
            $table->foreignId('assigned_to')->nullable()->constrained('employees')->nullOnDelete();
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->enum('task_status', ['todo', 'in_progress', 'review', 'done', 'cancelled'])->default('todo');
            $table->date('start_date')->nullable();
            $table->date('due_date')->nullable();
            $table->date('completed_date')->nullable();
            $table->decimal('estimated_hours', 8, 2)->nullable();
            $table->unsignedTinyInteger('progress_percent')->default(0);
            $table->unsignedSmallInteger('sort_order')->default(1);
            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_tasks');
    }
};
