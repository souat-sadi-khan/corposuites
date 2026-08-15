<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Who is on a project's team, in what role, and for how much of their time.
     *
     * A flat membership table rather than a team header + members: the project
     * already IS the header, so a separate "team" record would add a level with
     * nothing of its own on it. One row per employee per project (unique), with
     * joined/left dates carrying the history of who was on it and when.
     *
     * employee_id FKs the existing HRM `employees` table per the
     * Naming/Table Conflict Guard — no parallel staff table.
     */
    public function up(): void
    {
        Schema::create('project_team_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->enum('team_role', ['lead', 'member', 'analyst', 'developer', 'designer', 'tester', 'consultant', 'support'])->default('member');
            $table->decimal('allocation_percent', 5, 2)->default(100);
            $table->date('joined_date');
            $table->date('left_date')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->unique(['project_id', 'employee_id'], 'ptm_project_employee_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_team_members');
    }
};
