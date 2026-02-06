<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Ajoute les colonnes de géolocalisation (latitude, longitude) aux tables
     * regions, provinces et communes pour permettre la recherche géospatiale.
     */
    public function up(): void
    {
        // Ajouter les coordonnées aux régions
        Schema::table('regions', function (Blueprint $table) {
            $table->decimal('latitude', 10, 8)->nullable()->after('code');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude');

            // Index pour améliorer les performances des requêtes géospatiales
            $table->index(['latitude', 'longitude'], 'regions_coordinates_index');
        });

        // Ajouter les coordonnées aux provinces
        Schema::table('provinces', function (Blueprint $table) {
            $table->decimal('latitude', 10, 8)->nullable()->after('code');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude');

            // Index pour améliorer les performances des requêtes géospatiales
            $table->index(['latitude', 'longitude'], 'provinces_coordinates_index');
        });

        // Ajouter les coordonnées aux communes
        Schema::table('communes', function (Blueprint $table) {
            $table->decimal('latitude', 10, 8)->nullable()->after('code_postal');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude');

            // Index pour améliorer les performances des requêtes géospatiales
            $table->index(['latitude', 'longitude'], 'communes_coordinates_index');
        });

        // Activer l'extension PostGIS si disponible (optionnel, pour PostgreSQL)
        // Décommenter si vous voulez utiliser les fonctions géospatiales avancées de PostGIS
        // DB::statement('CREATE EXTENSION IF NOT EXISTS postgis');

        // Alternative: utiliser l'extension earthdistance pour PostgreSQL
        // DB::statement('CREATE EXTENSION IF NOT EXISTS cube');
        // DB::statement('CREATE EXTENSION IF NOT EXISTS earthdistance');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Supprimer les colonnes des communes
        Schema::table('communes', function (Blueprint $table) {
            $table->dropIndex('communes_coordinates_index');
            $table->dropColumn(['latitude', 'longitude']);
        });

        // Supprimer les colonnes des provinces
        Schema::table('provinces', function (Blueprint $table) {
            $table->dropIndex('provinces_coordinates_index');
            $table->dropColumn(['latitude', 'longitude']);
        });

        // Supprimer les colonnes des régions
        Schema::table('regions', function (Blueprint $table) {
            $table->dropIndex('regions_coordinates_index');
            $table->dropColumn(['latitude', 'longitude']);
        });
    }
};
