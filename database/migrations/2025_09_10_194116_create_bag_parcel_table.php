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
        Schema::create('bag_parcel', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bag_id')->constrained('bags')->onDelete('cascade');
            $table->string('sscc'); // GS1 SSCC code
            $table->timestamp('added_at')->useCurrent();
            $table->timestamps();

            $table->unique(['bag_id', 'sscc']);
            $table->index('sscc');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bag_parcel');
    }
};
