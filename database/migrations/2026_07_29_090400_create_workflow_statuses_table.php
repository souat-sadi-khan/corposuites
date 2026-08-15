<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('workflow_statuses', function (Blueprint $table) {
            $table->id();

            $table->foreignId('workflow_definition_id')->constrained('workflow_definitions')->cascadeOnDelete();
            $table->string('key');
            $table->string('label');
            $table->string('color')->nullable();
            $table->boolean('is_terminal')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['workflow_definition_id', 'key']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('workflow_statuses');
    }
};
