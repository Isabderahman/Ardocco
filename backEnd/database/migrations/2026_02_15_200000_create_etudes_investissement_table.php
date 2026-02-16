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
        Schema::create('etudes_investissement', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('listing_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('created_by')->constrained('users')->onDelete('cascade');

            // Project info
            $table->string('titre_projet')->nullable();
            $table->string('type_projet')->nullable(); // R+4, R+5, 1S/SOL R+4, etc.
            $table->integer('nombre_sous_sols')->default(0);
            $table->integer('nombre_etages')->default(4);
            $table->string('localisation')->nullable();
            $table->string('version')->nullable();

            // Terrain inputs
            $table->decimal('superficie_terrain', 15, 2); // m²
            $table->decimal('prix_terrain_m2', 15, 2); // DHS/m²
            $table->decimal('taux_immatriculation', 5, 2)->default(5.50); // %

            // Construction surfaces (JSON for flexibility)
            $table->json('surfaces_par_niveau')->nullable();
            /*
            {
                "sous_sol_1": 300,
                "rdc": 300,
                "etage_1": 300,
                ...
            }
            */
            $table->decimal('surface_plancher_total', 15, 2)->nullable();

            // Construction costs per m²
            $table->decimal('cout_gros_oeuvres_m2', 15, 2)->default(1300);
            $table->decimal('cout_finition_m2', 15, 2)->default(2700);
            $table->decimal('amenagement_divers', 15, 2)->default(350000);

            // Additional fees
            $table->decimal('frais_groupement_etudes', 15, 2)->nullable();
            $table->decimal('frais_autorisation_eclatement', 15, 2)->default(450000);
            $table->decimal('frais_lydec', 15, 2)->default(270000);

            // Sellable surfaces
            $table->json('surfaces_vendables')->nullable();
            /*
            {
                "rdc": {"usage": "apparts", "surface": 195},
                "mezzanine": {"usage": null, "surface": 0},
                "etages": {"usage": "apparts", "surface": 984}
            }
            */
            $table->decimal('surface_vendable_commerce', 15, 2)->default(0);
            $table->decimal('surface_vendable_appart', 15, 2)->default(0);

            // Selling prices
            $table->decimal('prix_vente_m2_commerce', 15, 2)->nullable();
            $table->decimal('prix_vente_m2_appart', 15, 2)->default(18000);

            // Calculated fields (stored for quick access)
            $table->decimal('prix_terrain_total', 15, 2)->nullable();
            $table->decimal('frais_immatriculation', 15, 2)->nullable();
            $table->decimal('cout_total_travaux', 15, 2)->nullable();
            $table->decimal('total_frais_construction', 15, 2)->nullable();
            $table->decimal('total_investissement', 15, 2)->nullable();
            $table->decimal('revenus_commerce', 15, 2)->nullable();
            $table->decimal('revenus_appart', 15, 2)->nullable();
            $table->decimal('total_revenues', 15, 2)->nullable();
            $table->decimal('resultat_brute', 15, 2)->nullable();
            $table->decimal('ratio', 8, 2)->nullable(); // %

            // Plans/images uploaded
            $table->json('plans')->nullable(); // Array of document IDs or paths

            // AI processing
            $table->boolean('generated_by_ai')->default(false);
            $table->json('ai_extracted_data')->nullable();
            $table->text('ai_notes')->nullable();

            // Status workflow
            $table->enum('status', ['draft', 'pending_review', 'approved', 'rejected'])->default('draft');
            $table->foreignUuid('reviewed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();

            // PDF
            $table->string('pdf_path')->nullable();
            $table->timestamp('pdf_generated_at')->nullable();

            $table->timestamps();

            $table->index(['listing_id', 'status']);
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('etudes_investissement');
    }
};
