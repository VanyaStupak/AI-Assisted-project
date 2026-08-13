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
        Schema::table('promo_code_claims', function (Blueprint $table) {
            $table->enum('status', ['applied', 'rejected', 'revoked'])->default('applied')->change();
            $table->timestamp('revoked_at')->nullable()->after('bonus_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('promo_code_claims', function (Blueprint $table) {
            $table->dropColumn('revoked_at');
            $table->enum('status', ['applied', 'rejected'])->default('applied')->change();
        });
    }
};
