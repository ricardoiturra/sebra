<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        Role::findOrCreate('full admin', 'web');
    }

    public function down(): void
    {
        // The role may already have been assigned to existing accounts.
    }
};
