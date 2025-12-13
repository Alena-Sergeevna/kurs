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
        Schema::create('didactic_unit_drafts', function (Blueprint $table) {
            $table->id();
            
            // UUID для группировки всех изменений в одном черновике
            $table->uuid('draft_batch_id');
            
            // Контекст связи
            $table->string('subject_type'); // modul или op
            $table->unsignedBigInteger('subject_id');
            $table->unsignedBigInteger('prof_competency_id');
            
            // Исходная ДЕ (может быть null если создаем новую)
            $table->unsignedBigInteger('original_didactic_unit_id')->nullable();
            
            // Для объединения нескольких ДЕ (JSON массив ID)
            $table->json('original_didactic_unit_ids')->nullable();
            
            // Новая ДЕ (может быть существующая или новая)
            $table->unsignedBigInteger('new_didactic_unit_id')->nullable(); // если используем существующую
            
            // Если создаем новую ДЕ
            $table->string('new_didactic_unit_name')->nullable();
            $table->string('new_didactic_unit_type')->nullable(); // know, be_able, have_practical_experience
            
            // Действие: keep, replace, move, merge, create, remove
            $table->string('action'); // keep/replace/move/merge/create/remove
            
            // Куда переносим (если action = move)
            $table->string('target_subject_type')->nullable();
            $table->unsignedBigInteger('target_subject_id')->nullable();
            $table->unsignedBigInteger('target_prof_competency_id')->nullable();
            
            // Комментарий преподавателя
            $table->text('comment')->nullable();
            
            // Кто создал черновик
            $table->unsignedBigInteger('created_by')->nullable();
            
            $table->timestamps();
            
            // Индексы
            $table->index('draft_batch_id');
            $table->index(['subject_type', 'subject_id', 'prof_competency_id']);
            $table->index('original_didactic_unit_id');
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('didactic_unit_drafts');
    }
};
