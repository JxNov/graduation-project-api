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
        Schema::create('submitted_assignments', function (Blueprint $table) {
            $table->id();
            $table->timestamp('submitted_at');
            $table->text('file_path');
            $table->decimal('score', 5, 2)->nullable();
            $table->text('feedback')->nullable();

            $table->foreignId('assignment_id')
                ->constrained('assignments')
                ->onDelete('cascade');

            $table->foreignId('student_id')
                ->constrained('users')
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
        Schema::dropIfExists('submitted_assignments');
    }
};
