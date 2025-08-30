<?php

use App\Enums\Status;
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
        Schema::create('news_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author')->nullable()->constrained('users')->onUpdate('cascade')->onDelete('cascade');
            $table->string('title')->nullable();
            $table->longText('description')->nullable();
            $table->foreignId('file')->nullable()->constrained('uploads')->onUpdate('cascade')->onDelete('cascade');
            $table->date('date')->nullable();
            $table->unsignedTinyInteger('status')->default(Status::ACTIVE)->comment(
                'active       = 1,
                inactive      = 0'
            );
            $table->timestamps();

            $table->index('author');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('news_offers');
    }
};
