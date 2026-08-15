<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('workflow_instance_approvals', function (Blueprint $table) {
            $table->id();

            $table->foreignId('workflow_instance_id')->constrained('workflow_instances')->cascadeOnDelete();
            $table->foreignId('workflow_step_id')->constrained('workflow_steps')->cascadeOnDelete();
            $table->foreignId('approver_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->enum('action', ['approved', 'rejected', 'on_hold', 'resubmitted', 'commented']);
            $table->text('remarks')->nullable();
            $table->timestamp('acted_at')->nullable();
            $table->timestamps();

            $table->index('workflow_instance_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('workflow_instance_approvals');
    }
};
