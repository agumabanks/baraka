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
        Schema::create('bags', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('origin_branch_id')->constrained('hubs')->onDelete('cascade');
            $table->foreignId('dest_branch_id')->constrained('hubs')->onDelete('cascade');
            $table->string('status')->default('OPEN'); // OPEN, CLOSED, IN_TRANSIT
            $table->foreignId('leg_id')->nullable()->constrained('transport_legs')->onDelete('set null');
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['origin_branch_id', 'dest_branch_id']);
            $table->index('status');
            $table->index('leg_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bags');
    }
};
