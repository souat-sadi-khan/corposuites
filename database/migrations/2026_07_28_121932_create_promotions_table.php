<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('from_designation')->nullable();
            $table->string('to_designation');
            $table->decimal('from_salary', 12, 2)->nullable();
            $table->decimal('to_salary', 12, 2)->nullable();
            $table->date('promotion_date');
            $table->text('remarks')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('promotions');
    }
};
