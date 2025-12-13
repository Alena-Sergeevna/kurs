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
        Schema::create('approved_versions', function (Blueprint $table) {
            $table->id();
            
            // Номер версии
            $table->unsignedInteger('version_number');
            
            // Кто утвердил
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            
            // Краткое описание изменений
            $table->text('changes_summary')->nullable();
            
            // Снимки состояния до и после (JSON)
            $table->json('snapshot_before')->nullable();
            $table->json('snapshot_after')->nullable();
            
            // UUID черновика, который был применен
            $table->uuid('draft_batch_id')->nullable();
            
            $table->timestamps();
            
            // Индексы
            $table->index('version_number');
            $table->index('draft_batch_id');
            $table->index('approved_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approved_versions');
    }
};
