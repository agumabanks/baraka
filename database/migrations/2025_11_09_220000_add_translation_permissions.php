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
        // Add translation permissions to existing permissions system
        // This will be handled through the existing permission seeding mechanism
        // The actual permission names may need to be added to the existing seeder or created separately
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove translation permissions if needed
    }
};
