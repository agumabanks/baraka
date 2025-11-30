<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('branch_attendances')) {
            return;
        }

        Schema::create('branch_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->foreignId('worker_id')->constrained('branch_workers')->onDelete('cascade');
            $table->date('shift_date');
            $table->dateTime('start_at')->nullable();
            $table->dateTime('end_at')->nullable();
            $table->dateTime('check_in_at')->nullable();
            $table->dateTime('check_out_at')->nullable();
            $table->string('status')->default('SCHEDULED');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'shift_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_attendances');
    }
};
