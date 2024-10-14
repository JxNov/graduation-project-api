<?php

use App\Models\ClassPeriod;
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
        Schema::create('class_periods', function (Blueprint $table) {
            $table->id();
            $table->enum('lesson', ClassPeriod::_CLASS_PERIOD)->default(ClassPeriod::_CLASS_PERIOD["Tiết 1"]);
            $table->time('start_time')->default(ClassPeriod::_TIME_CLASS_PERIOD_AM["Tiết 1"]["start_time"]);
            $table->time('end_time')->default(ClassPeriod::_TIME_CLASS_PERIOD_AM["Tiết 1"]["end_time"]);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_periods');
    }
};
