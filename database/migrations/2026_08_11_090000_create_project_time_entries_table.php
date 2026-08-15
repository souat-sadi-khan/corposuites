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
        Schema::create('project_time_entries', function (Blueprint $table) {
            $table->id();

            // An entry has no meaning without who logged it or which project
            // it was logged against — both required parent-owns-child FKs,
            // cascadeOnDelete, same reasoning as ProjectTeamMember's own
            // employee_id/project_id pair.
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();

            // Optional — time can be logged against the project generally,
            // not necessarily one specific task.
            $table->foreignId('project_task_id')->nullable()->constrained('project_tasks')->nullOnDelete();

            $table->date('work_date');

            // Either both clock timestamps are set (a real start/stop timer
            // entry) or neither is, and `hours` carries a manually-typed
            // duration instead — enforced in the Form Request, not here.
            $table->dateTime('started_at')->nullable();
            $table->dateTime('ended_at')->nullable();

            // Server-computed from started_at/ended_at when both are present;
            // otherwise the manually-entered duration.
            $table->decimal('hours', 6, 2)->nullable();

            $table->boolean('is_billable')->default(true);
            $table->text('description')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_time_entries');
    }
};
