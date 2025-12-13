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
        Schema::table('subject_didactic_unit_prof_competency', function (Blueprint $table) {
            $table->boolean('approved')->default(false)->after('prof_competency_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subject_didactic_unit_prof_competency', function (Blueprint $table) {
            $table->dropColumn('approved');
        });
    }
};
