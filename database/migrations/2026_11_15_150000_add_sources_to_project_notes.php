<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_notes', function (Blueprint $table): void {
            $table->string('source')->nullable();
            $table->string('revision', 64)->nullable();
            $table->unique(['project_id', 'source']);
        });
    }

    public function down(): void
    {
        Schema::table('project_notes', function (Blueprint $table): void {
            $table->dropUnique(['project_id', 'source']);
            $table->dropColumn(['source', 'revision']);
        });
    }
};
