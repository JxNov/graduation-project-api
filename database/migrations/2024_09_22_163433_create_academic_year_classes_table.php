<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('academic_year_classes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('academic_year_id')
                ->constrained('academic_years')
                ->onDelete('cascade');

            $table->foreignId('class_id')
                ->constrained('classes')
                ->onDelete('cascade');

            $table->timestamps();

            $table->unique(['academic_year_id', 'class_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_year_classes');
    }
};
