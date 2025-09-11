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
        Schema::create('scan_events', function (Blueprint $table) {
            $table->id();
            $table->string('sscc'); // GS1 SSCC code
            $table->enum('type', array_column(\App\Enums\ScanType::cases(), 'value'));
            $table->foreignId('branch_id')->constrained('hubs')->onDelete('cascade');
            $table->foreignId('leg_id')->nullable()->constrained('transport_legs')->onDelete('set null');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamp('occurred_at');
            $table->json('geojson')->nullable(); // {"type":"Point","coordinates":[lng,lat]}
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['sscc', 'occurred_at']);
            $table->index('type');
            $table->index('branch_id');
            $table->index('leg_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scan_events');
    }
};
