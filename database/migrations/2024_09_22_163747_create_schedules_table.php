<?php

use App\Models\Schedule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();

            $table->foreignId('class_id')
                ->constrained('classes')
                ->onDelete('cascade');

            $table->foreignId('subject_id')
                ->constrained('subjects')
                ->onDelete('cascade');

            $table->foreignId('teacher_id')
                ->constrained('users')
                ->onDelete('cascade');

            $table->enum('days', Schedule::_DAYS)->default(Schedule::_DAYS["Monday"]);

            $table->foreignId('class_period_id')
                ->constrained('class_periods')
                ->onDelete('cascade')
                ->nullable();
                
            $table->boolean('is_morning')->nullable()->default(true);

            $table->unique(['class_id', 'subject_id', 'class_period_id', 'days'], 'unique_schedule');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
