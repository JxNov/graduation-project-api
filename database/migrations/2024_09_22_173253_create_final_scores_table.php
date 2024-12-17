<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('final_scores', function (Blueprint $table) {
            $table->id();
            $table->decimal('average_score', 5, 2);

            $table->foreignId('student_id')
                ->constrained('users')
                ->onDelete('cascade');

            $table->foreignId('academic_year_id')
                ->constrained('academic_years')
                ->onDelete('cascade');


            $table->foreignId('semester_id')->nullable()
                ->constrained('semesters')
                ->onDelete('cascade');

            $table->foreignId('class_id')
                ->constrained('classes')
                ->onDelete('cascade');

            $table->string('performance_level', 50)->nullable();

            $table->unique(['student_id', 'academic_year_id', 'semester_id'], 'unique_final_scores');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('final_scores');
    }
};
