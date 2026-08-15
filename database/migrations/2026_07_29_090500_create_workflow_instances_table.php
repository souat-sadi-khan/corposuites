<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('workflow_instances', function (Blueprint $table) {
            $table->id();

            $table->foreignId('workflow_definition_id')->constrained('workflow_definitions')->cascadeOnDelete();
            $table->string('approvable_type');
            $table->unsignedBigInteger('approvable_id');
            $table->foreignId('current_step_id')->nullable()->constrained('workflow_steps')->nullOnDelete();
            $table->string('current_status')->default('pending');
            $table->foreignId('initiated_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->foreignId('resubmission_of')->nullable()->constrained('workflow_instances')->nullOnDelete();
            $table->unsignedInteger('resubmission_count')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['approvable_type', 'approvable_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('workflow_instances');
    }
};
