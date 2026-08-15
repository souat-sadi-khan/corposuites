<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('workflow_notification_triggers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('workflow_definition_id')->constrained('workflow_definitions')->cascadeOnDelete();
            $table->enum('event', ['step_pending', 'approved', 'rejected', 'resubmitted', 'completed']);
            $table->enum('notify_type', ['role', 'user', 'initiator', 'approver']);
            $table->unsignedBigInteger('notify_id')->nullable();
            $table->text('template_message')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('workflow_notification_triggers');
    }
};
