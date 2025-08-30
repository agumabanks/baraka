<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('general_settings', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->longText('address')->nullable();
            $table->string('currency')->nullable();
            $table->string('copyright')->nullable();
            $table->integer('logo')->nullable();
            $table->integer('light_logo')->nullable();
            $table->integer('favicon')->nullable();
            $table->string('current_version')->nullable();
            $table->string('par_track_prefix')->nullable();
            $table->string('invoice_prefix')->nullable();
            $table->string('primary_color')->default('#7e0095')->nullable();
            $table->string('text_color')->default('#ffffff')->nullable();
            $table->string('version')->default('1.4')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('general_settings');
    }
};
