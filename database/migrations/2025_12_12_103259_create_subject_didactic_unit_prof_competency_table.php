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
        Schema::create('subject_didactic_unit_prof_competency', function (Blueprint $table) {
            $table->id();
            $table->string('subject_type'); // 'modul' или 'op'
            $table->unsignedBigInteger('subject_id');
            $table->foreignId('didactic_unit_id')->constrained('didactic_units')->onDelete('cascade');
            $table->foreignId('prof_competency_id')->constrained('prof_competencies')->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['subject_type', 'subject_id', 'didactic_unit_id', 'prof_competency_id'], 'subject_de_pk_unique');
            $table->index(['subject_type', 'subject_id', 'prof_competency_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subject_didactic_unit_prof_competency');
    }
};
