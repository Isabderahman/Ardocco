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
        Schema::create('fiches_juridiques', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('listing_id')->unique()->constrained()->onDelete('cascade');
            $table->enum('statut_foncier', [
                'titre_foncier', 'melk', 'collectif', 'habous', 'domaine_public'
            ])->nullable();
            $table->string('numero_titre', 100)->nullable();
            $table->text('proprietaire_legal')->nullable();
            $table->json('servitudes')->nullable();
            $table->json('restrictions')->nullable();
            $table->json('litiges')->nullable();
            $table->json('documents_manquants')->nullable();
            $table->json('points_vigilance')->nullable();
            $table->boolean('conformite_urbanisme')->nullable();
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
        Schema::dropIfExists('fiches_juridiques');
    }
};
