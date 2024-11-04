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
        Schema::create('class_materials', function (Blueprint $table) {
            $table->id();

            $table->foreignId('material_id')
                ->constrained('materials')
                ->onDelete('cascade');

            $table->foreignId('class_id')
                ->constrained('classes')
                ->onDelete('cascade');

            $table->unique(['material_id', 'class_id']);   
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_materials');
    }
};
