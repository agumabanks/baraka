<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Foreign key intentionally managed in base migration to avoid dependency ordering issues.
    }

    public function down(): void
    {
        // No-op.
    }
};
