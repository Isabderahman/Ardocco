<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addExpertColumns('fiches_techniques', includeRentabilite: false);
        $this->addExpertColumns('fiches_financieres', includeRentabilite: true);
        $this->addExpertColumns('fiches_juridiques', includeRentabilite: false);

        // Add is_featured to listings
        if (Schema::hasTable('listings') && !Schema::hasColumn('listings', 'is_featured')) {
            Schema::table('listings', function (Blueprint $table) {
                $table->boolean('is_featured')->default(false);
            });
        }
    }

    public function down(): void
    {
        $this->dropColumnsIfExist('fiches_techniques', ['expert_notes', 'conclusion', 'rating', 'attached_documents']);
        $this->dropColumnsIfExist('fiches_financieres', ['rentabilite', 'expert_notes', 'conclusion', 'rating', 'attached_documents']);
        $this->dropColumnsIfExist('fiches_juridiques', ['expert_notes', 'conclusion', 'rating', 'attached_documents']);
        $this->dropColumnsIfExist('listings', ['is_featured']);
    }

    private function addExpertColumns(string $tableName, bool $includeRentabilite): void
    {
        if (!Schema::hasTable($tableName)) {
            return;
        }

        $missing = [];
        if ($includeRentabilite && !Schema::hasColumn($tableName, 'rentabilite')) {
            $missing[] = 'rentabilite';
        }
        foreach (['expert_notes', 'conclusion', 'rating', 'attached_documents'] as $column) {
            if (!Schema::hasColumn($tableName, $column)) {
                $missing[] = $column;
            }
        }

        if ($missing === []) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($missing, $includeRentabilite) {
            if ($includeRentabilite && in_array('rentabilite', $missing, true)) {
                $table->decimal('rentabilite', 10, 2)->nullable();
            }
            if (in_array('expert_notes', $missing, true)) {
                $table->text('expert_notes')->nullable();
            }
            if (in_array('conclusion', $missing, true)) {
                $table->text('conclusion')->nullable();
            }
            if (in_array('rating', $missing, true)) {
                $table->tinyInteger('rating')->nullable();
            }
            if (in_array('attached_documents', $missing, true)) {
                $table->json('attached_documents')->nullable();
            }
        });
    }

    private function dropColumnsIfExist(string $tableName, array $columns): void
    {
        if (!Schema::hasTable($tableName)) {
            return;
        }

        $existing = array_values(array_filter($columns, fn (string $c) => Schema::hasColumn($tableName, $c)));
        if ($existing === []) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($existing) {
            $table->dropColumn($existing);
        });
    }
};
