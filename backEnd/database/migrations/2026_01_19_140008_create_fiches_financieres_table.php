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
        Schema::create('fiches_financieres', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('listing_id')->unique()->constrained()->onDelete('cascade');
            $table->decimal('prix_marche_estime', 15, 2)->nullable();
            $table->json('comparables')->nullable();
            $table->json('hypotheses_valorisation')->nullable();
            $table->decimal('couts_viabilisation', 15, 2)->nullable();
            $table->decimal('couts_amenagement', 15, 2)->nullable();
            $table->json('taxes_estimees')->nullable();
            $table->json('rentabilite_potentielle')->nullable();
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
        Schema::dropIfExists('fiches_financieres');
    }
};
