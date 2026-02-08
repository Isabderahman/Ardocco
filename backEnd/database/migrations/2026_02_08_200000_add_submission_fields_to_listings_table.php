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
        Schema::table('listings', function (Blueprint $table) {
            $table->string('contact_phone', 20)->nullable()->after('agent_id');
            $table->string('contact_whatsapp', 20)->nullable()->after('contact_phone');
            $table->string('contact_email', 255)->nullable()->after('contact_whatsapp');
            $table->boolean('owner_attestation')->default(false)->after('contact_email');

            $table->boolean('superficie_unknown')->default(false)->after('superficie');
            $table->boolean('price_on_request')->default(false)->after('prix_demande');
            $table->boolean('show_price_per_m2')->default(false)->after('prix_par_m2');
            $table->boolean('negotiable')->default(false)->after('show_price_per_m2');

            $table->string('perimetre', 20)->nullable()->after('zonage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->dropColumn([
                'contact_phone',
                'contact_whatsapp',
                'contact_email',
                'owner_attestation',
                'superficie_unknown',
                'price_on_request',
                'show_price_per_m2',
                'negotiable',
                'perimetre',
            ]);
        });
    }
};

