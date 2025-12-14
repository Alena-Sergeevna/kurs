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
        // Добавляем поле approved_version_id в subject_prof_competency
        Schema::table('subject_prof_competency', function (Blueprint $table) {
            $table->unsignedBigInteger('approved_version_id')->nullable()->after('approved');
            $table->index('approved_version_id');
        });

        // Добавляем поле approved_version_id в subject_didactic_unit_prof_competency
        Schema::table('subject_didactic_unit_prof_competency', function (Blueprint $table) {
            $table->unsignedBigInteger('approved_version_id')->nullable()->after('approved');
            $table->index('approved_version_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subject_prof_competency', function (Blueprint $table) {
            $table->dropIndex(['approved_version_id']);
            $table->dropColumn('approved_version_id');
        });

        Schema::table('subject_didactic_unit_prof_competency', function (Blueprint $table) {
            $table->dropIndex(['approved_version_id']);
            $table->dropColumn('approved_version_id');
        });
    }
};
