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
        Schema::create('subject_scores', function (Blueprint $table) {
            $table->id();
            $table->json('detailed_scores');
            $table->decimal('average_score', 5, 2);

            $table->foreignId('subject_id')
                ->constrained('subjects')
                ->onDelete('cascade');

            $table->foreignId('student_id')
                ->constrained('users')
                ->onDelete('cascade');

            $table->foreignId('semester_id')
                ->constrained('semesters')
                ->onDelete('cascade');

            $table->foreignId('class_id')
                ->constrained('classes')
                ->onDelete('cascade');

            // điểm của mỗi hs trong mỗi kỳ duy nhất
            $table->unique(['subject_id', 'student_id', 'semester_id'], 'unique_subject_scores');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subject_scores');
    }
};
