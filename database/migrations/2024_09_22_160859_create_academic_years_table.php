<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_years', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
            $table->string('slug', 70)->unique();
            $table->date('start_date');
            $table->date('end_date');

            $table->foreignId('generation_id')
                ->constrained('generations')
                ->onDelete('cascade');
            
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['start_date', 'end_date'], 'academic_years_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_years');
    }
};
