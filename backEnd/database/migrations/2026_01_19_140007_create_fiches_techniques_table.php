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
        Schema::create('fiches_techniques', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('listing_id')->unique()->constrained()->onDelete('cascade');
            $table->json('accessibilite')->nullable();
            $table->json('voisinage')->nullable();
            $table->json('contraintes_techniques')->nullable();
            $table->json('opportunites')->nullable();
            $table->json('equipements')->nullable();
            $table->json('photos_analyse')->nullable();
            $table->boolean('generated_by_ai')->default(false);
            $table->foreignUuid('validated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('validated_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fiches_techniques');
    }
};
