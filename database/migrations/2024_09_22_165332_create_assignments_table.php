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
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->string('title', 100);
            $table->text('description')->nullable();
            $table->date('due_date');
            $table->text('criteria');

            $table->foreignId('subject_id')
                ->constrained('subjects')
                ->onDelete('cascade');

            $table->foreignId('teacher_id')
                ->constrained('users')
                ->onDelete('cascade');

            $table->foreignId('class_id')
                ->constrained('classes')
                ->onDelete('cascade');

            $table->foreignId('semester_id')
                ->constrained('semesters')
                ->onDelete('cascade');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assignments');
    }
};
