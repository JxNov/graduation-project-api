<?php

use App\Models\AttendanceDetail;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('attendance_details', function (Blueprint $table) {
            $table->id();

            $table->foreignId('attendance_id')
                ->constrained('attendances')
                ->onDelete('cascade');

            $table->foreignId('student_id')
                ->constrained('users')
                ->onDelete('cascade');

            $table->enum('status', AttendanceDetail::_STATUS)
            ->default(AttendanceDetail::_STATUS['Absent']);
            
            $table->text('reason')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_details');
    }
};
