<?php

use App\Models\Attendance;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->dateTime('date');
            $table->enum('status', Attendance::_STATUS)->default(Attendance::_STATUS['Absent']);
            $table->text('reason')->nullable();

            $table->foreignId('student_id')
                ->constrained('users')
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
        Schema::dropIfExists('attendances');
    }
};
