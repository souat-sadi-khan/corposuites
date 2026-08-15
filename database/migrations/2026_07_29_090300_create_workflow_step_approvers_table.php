<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('workflow_step_approvers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('workflow_step_id')->constrained('workflow_steps')->cascadeOnDelete();
            $table->enum('approver_type', ['role', 'user', 'designation']);
            $table->unsignedBigInteger('approver_id')->nullable();
            $table->timestamps();

            $table->index('workflow_step_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('workflow_step_approvers');
    }
};
