<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('event_streams');

        Schema::create('event_streams', function (Blueprint $table) {
            $table->id();
            $table->string('event_type');
            $table->string('aggregate_type');
            $table->string('aggregate_id');
            $table->foreignId('actor_id')->nullable()->constrained('users');
            $table->json('payload');
            $table->json('metadata')->nullable();
            $table->timestamp('timestamp');
            $table->timestamps();

            $table->index(['aggregate_type', 'aggregate_id', 'timestamp']);
            $table->index('event_type');
            $table->index('timestamp');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_streams');
    }
};
