<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->decimal('cout_investissement', 15, 2)->nullable()->after('hauteur_max');
            $table->decimal('ratio', 5, 2)->nullable()->after('cout_investissement');
            $table->string('user_role', 50)->nullable()->after('owner_attestation');
        });
    }

    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->dropColumn(['cout_investissement', 'ratio', 'user_role']);
        });
    }
};
