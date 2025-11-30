<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('scheduled_reports')) {
            Schema::create('scheduled_reports', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->string('name');
                $table->string('report_type'); // shipment, financial, performance
                $table->json('filters')->nullable();
                $table->string('schedule_type'); // daily, weekly, monthly
                $table->unsignedTinyInteger('schedule_day')->nullable(); // 0-6 for weekly, 1-28 for monthly
                $table->time('schedule_time')->default('08:00:00');
                $table->json('email_recipients')->nullable();
                $table->string('export_format')->default('pdf'); // pdf, csv, xlsx
                $table->boolean('is_active')->default(true);
                $table->timestamp('last_run_at')->nullable();
                $table->timestamp('next_run_at')->nullable();
                $table->timestamps();

                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->index(['is_active', 'next_run_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_reports');
    }
};
