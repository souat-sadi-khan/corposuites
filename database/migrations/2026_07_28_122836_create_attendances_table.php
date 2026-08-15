<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();

            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->date('attendance_date');
            $table->time('check_in')->nullable();
            $table->time('check_out')->nullable();
            $table->enum('attendance_status', ['present', 'absent', 'half_day', 'on_leave', 'late'])->default('present');
            $table->text('remarks')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->unique(['employee_id', 'attendance_date']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('attendances');
    }
};
