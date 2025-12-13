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
        Schema::create('subject_prof_competency_drafts', function (Blueprint $table) {
            $table->id();
            
            // Исходная связь
            $table->string('original_subject_type'); // modul или op
            $table->unsignedBigInteger('original_subject_id');
            $table->unsignedBigInteger('original_prof_competency_id');
            
            // Новая связь (может быть та же, если только меняем ДЕ)
            $table->string('new_subject_type'); // modul или op
            $table->unsignedBigInteger('new_subject_id');
            $table->unsignedBigInteger('new_prof_competency_id');
            
            // Действие: keep (оставить), move (перенести), remove (удалить)
            $table->string('action')->default('keep');
            
            // Комментарий преподавателя
            $table->text('comment')->nullable();
            
            // Кто создал черновик (пока nullable, потом добавим users)
            $table->unsignedBigInteger('created_by')->nullable();
            
            // UUID для группировки связанных черновиков
            $table->uuid('draft_batch_id')->nullable();
            
            $table->timestamps();
            
            // Индексы
            $table->index(['original_subject_type', 'original_subject_id', 'original_prof_competency_id']);
            $table->index('draft_batch_id');
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subject_prof_competency_drafts');
    }
};
