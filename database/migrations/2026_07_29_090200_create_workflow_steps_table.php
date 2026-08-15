<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('workflow_steps', function (Blueprint $table) {
            $table->id();

            $table->foreignId('workflow_definition_id')->nullable()->constrained('workflow_definitions')->cascadeOnDelete();
            $table->foreignId('workflow_template_id')->nullable()->constrained('workflow_templates')->cascadeOnDelete();
            $table->unsignedInteger('step_order');
            $table->string('name');
            $table->enum('approval_type', ['single', 'all_must_approve', 'any_one_approves'])->default('single');
            $table->timestamps();

            $table->index(['workflow_definition_id', 'step_order']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('workflow_steps');
    }
};
