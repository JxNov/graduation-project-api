<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('block_materials', function (Blueprint $table) {
            $table->id();

            $table->foreignId('material_id')
                ->constrained('materials')
                ->onDelete('cascade');

            $table->foreignId('block_id')
                ->constrained('blocks')
                ->onDelete('cascade');

            $table->unique(['block_id', 'material_id']);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('block_materials');
    }
};
