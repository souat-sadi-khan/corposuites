<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('salary_templates', function (Blueprint $table) {
            $table->id();

            $table->string('name')->unique();
            $table->enum('pay_type', ['monthly', 'daily', 'commission'])->default('monthly');
            $table->decimal('basic_salary', 12, 2)->default(0);
            $table->decimal('gross_salary', 12, 2)->default(0);
            $table->text('description')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::create('salary_template_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('salary_template_id')->constrained('salary_templates')->cascadeOnDelete();
            $table->foreignId('salary_component_id')->constrained('salary_components')->cascadeOnDelete();
            $table->decimal('amount', 12, 2)->default(0);
            $table->timestamps();

            $table->unique(
                ['salary_template_id', 'salary_component_id'],
                'salary_template_item_component_unique'
            );
        });
    }

    public function down()
    {
        Schema::dropIfExists('salary_template_items');
        Schema::dropIfExists('salary_templates');
    }
};
