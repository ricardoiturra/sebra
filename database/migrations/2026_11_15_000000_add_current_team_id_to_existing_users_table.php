<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'current_team_id')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->foreignId('current_team_id')->nullable();
            });
        }
    }

    public function down(): void
    {
        // Fresh databases already define this column in the original users migration.
    }
};
