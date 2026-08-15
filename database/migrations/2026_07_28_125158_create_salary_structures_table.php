<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('salary_structures', function (Blueprint $table) {
            $table->id();

            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->date('effective_date');
            $table->decimal('basic_salary', 12, 2)->default(0);
            $table->decimal('gross_salary', 12, 2)->default(0);
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::create('salary_structure_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('salary_structure_id')->constrained('salary_structures')->cascadeOnDelete();
            $table->foreignId('salary_component_id')->constrained('salary_components')->cascadeOnDelete();
            $table->decimal('amount', 12, 2)->default(0);
            $table->timestamps();

            $table->unique(
                ['salary_structure_id', 'salary_component_id'],
                'salary_item_component_unique'
            );
        });
    }

    public function down()
    {
        Schema::dropIfExists('salary_structure_items');
        Schema::dropIfExists('salary_structures');
    }
};
