<?php

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

        // Создаем универсальную таблицу
        Schema::create('subject_didactic_unit', function (Blueprint $table) {
            $table->id();
            $table->string('subject_type'); // 'modul' или 'op'
            $table->unsignedBigInteger('subject_id');
            $table->foreignId('didactic_unit_id')->constrained('didactic_units')->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['subject_type', 'subject_id', 'didactic_unit_id']);
            $table->index(['subject_type', 'subject_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subject_didactic_unit');
    }
};
