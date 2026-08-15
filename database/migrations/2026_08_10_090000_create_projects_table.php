<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The Project Management module's primary entity.
     *
     * FK notes:
     * - client_id / department_id / project_manager_id are all nullable with
     *   nullOnDelete. client_id is nevertheless REQUIRED in the Form Request:
     *   no project may be filed without a client, but deleting a client must
     *   not cascade away the delivery history filed under it. Same deliberate
     *   validation-vs-schema split already used for assets.asset_category_id.
     * - department_id FKs the existing HRM `departments` table and
     *   project_manager_id the existing `employees` table, per the
     *   Naming/Table Conflict Guard — no parallel staff/department tables.
     * - No budget column here: "Project Budgets" is its own checklist item and
     *   will attach through project_id.
     */
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('project_code')->unique(); // server-generated, immutable
            $table->string('name');
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('project_manager_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->date('actual_end_date')->nullable();
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->enum('project_status', ['planned', 'in_progress', 'on_hold', 'completed', 'cancelled'])->default('planned');
            // Manually maintained until the Tasks module exists to derive it.
            $table->unsignedTinyInteger('progress_percent')->default(0);
            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
