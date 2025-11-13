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
        if (!Schema::hasTable('edi_mappings')) {
            Schema::create('edi_mappings', function (Blueprint $table) {
                $table->id();
                $table->string('document_type', 10);
                $table->string('version')->nullable();
                $table->json('field_map');
                $table->text('description')->nullable();
                $table->boolean('active')->default(true);
                $table->timestamps();

                $table->unique(['document_type', 'version']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Intentional no-op: keep existing EDI mappings metadata intact.
    }
};
