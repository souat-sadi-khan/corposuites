<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The dated checkpoints a project is delivered against.
     *
     * Flat per-project records, ordered within their project by sort_order —
     * the project is the header, so no separate "milestone plan" level.
     *
     * Deliberately carries no billing amount: "Project Billing" is its own
     * checklist item and will attach through milestone_id if the client bills
     * on milestones. assigned_to FKs the existing HRM `employees` table per
     * the Naming/Table Conflict Guard.
     */
    public function up(): void
    {
        Schema::create('project_milestones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('name');
            $table->unsignedSmallInteger('sort_order')->default(1);
            $table->date('due_date');
            $table->date('completed_date')->nullable();
            $table->enum('milestone_status', ['pending', 'in_progress', 'completed', 'delayed', 'cancelled'])->default('pending');
            $table->foreignId('assigned_to')->nullable()->constrained('employees')->nullOnDelete();
            $table->text('deliverables')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->unique(['project_id', 'name'], 'pms_project_name_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_milestones');
    }
};
