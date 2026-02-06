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
        Schema::create('documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('listing_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('uploaded_by')->constrained('users')->onDelete('cascade');
            $table->enum('document_type', [
                'titre_foncier', 'plan_cadastral', 'note_renseignements',
                'mandat', 'photos', 'autres'
            ]);
            $table->string('file_name', 255);
            $table->text('file_path');
            $table->bigInteger('file_size');
            $table->string('mime_type', 100);
            $table->boolean('is_public')->default(false);
            $table->json('extracted_data')->nullable();
            $table->enum('processing_status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->timestamps();

            $table->index(['listing_id', 'document_type']);
            $table->index('processing_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
