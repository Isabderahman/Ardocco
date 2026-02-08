<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Account status for approval workflow
            $table->enum('account_status', [
                'pending_contract',  // Waiting for contract signature
                'pending_approval',  // Contract signed, waiting for admin
                'active',            // Approved and active
                'rejected',          // Rejected by admin
                'suspended',         // Temporarily suspended
            ])->default('active')->after('is_active');

            // Contract fields
            $table->string('contract_token', 64)->nullable()->after('account_status');
            $table->timestamp('contract_signed_at')->nullable()->after('contract_token');

            // Approval fields
            $table->uuid('approved_by')->nullable()->after('contract_signed_at');
            $table->timestamp('approved_at')->nullable()->after('approved_by');

            // Rejection fields
            $table->uuid('rejected_by')->nullable()->after('approved_at');
            $table->timestamp('rejected_at')->nullable()->after('rejected_by');
            $table->text('rejection_reason')->nullable()->after('rejected_at');

            // Additional registration fields
            $table->string('address', 500)->nullable()->after('company_name');
            $table->string('city', 100)->nullable()->after('address');
            $table->string('cin', 20)->nullable()->after('city');

            // Indexes
            $table->index('account_status');
            $table->index('contract_token');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['account_status']);
            $table->dropIndex(['contract_token']);

            $table->dropColumn([
                'account_status',
                'contract_token',
                'contract_signed_at',
                'approved_by',
                'approved_at',
                'rejected_by',
                'rejected_at',
                'rejection_reason',
                'address',
                'city',
                'cin',
            ]);
        });
    }
};
