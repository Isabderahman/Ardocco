<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('listings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('owner_id')->constrained('users')->onDelete('cascade');
            $table->foreignUuid('agent_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('reference', 50)->unique();
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->foreignUuid('commune_id')->constrained()->onDelete('restrict');
            $table->string('quartier', 100)->nullable();
            $table->text('address')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->decimal('superficie', 10, 2);
            $table->decimal('prix_demande', 15, 2);
            $table->decimal('prix_estime', 15, 2)->nullable();
            $table->decimal('prix_par_m2', 10, 2)->nullable();
            $table->enum('type_terrain', ['residentiel', 'commercial', 'industriel', 'agricole', 'mixte']);
            $table->enum('status', [
                'brouillon', 'soumis', 'en_revision',
                'valide', 'refuse', 'publie', 'vendu'
            ])->default('brouillon');
            $table->string('titre_foncier', 100)->nullable();
            $table->string('forme_terrain', 50)->nullable();
            $table->string('topographie', 50)->nullable();
            $table->json('viabilisation')->nullable();
            $table->string('zonage', 100)->nullable();
            $table->decimal('coefficient_occupation', 4, 2)->nullable();
            $table->integer('hauteur_max')->nullable();
            $table->boolean('is_exclusive')->default(false);
            $table->boolean('is_urgent')->default(false);
            $table->enum('visibility', ['public', 'private', 'restricted'])->default('public');
            $table->integer('views_count')->default(0);
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['owner_id', 'status']);
            $table->index('agent_id');
            $table->index('commune_id');
            $table->index(['type_terrain', 'status']);
            $table->index('prix_demande');
            $table->index('superficie');
            $table->index('published_at');
        });

        // Index géospatial pour recherche par coordonnées
        // Note: ll_to_earth requires PostgreSQL earthdistance extension
        // DB::statement('CREATE INDEX listings_location_idx ON listings USING GIST (ll_to_earth(latitude, longitude))');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('listings');
    }
};
