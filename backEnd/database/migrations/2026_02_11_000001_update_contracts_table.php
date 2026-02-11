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
        // Add signature column
        Schema::table('contracts', function (Blueprint $table) {
            $table->string('signature', 255)->nullable()->after('terms');
        });

        // Update contract_type enum to include 'acheteur'
        DB::statement("ALTER TABLE contracts MODIFY COLUMN contract_type ENUM('vendeur', 'promoteur', 'acheteur') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn('signature');
        });

        DB::statement("ALTER TABLE contracts MODIFY COLUMN contract_type ENUM('vendeur', 'promoteur') NOT NULL");
    }
};
