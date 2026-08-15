<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('workflow_definitions', function (Blueprint $table) {
            $table->id();

            $table->string('module_key');
            $table->string('name');
            $table->string('approvable_type');
            $table->enum('approval_mode', ['single', 'sequential', 'parallel'])->default('sequential');
            $table->foreignId('workflow_template_id')->nullable()->constrained('workflow_templates')->nullOnDelete();
            $table->boolean('status')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();

            $table->unique(['module_key', 'approvable_type']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('workflow_definitions');
    }
};
