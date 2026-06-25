<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Alinea el default de `currency` de vehicles y reservations a la moneda base
 * del proyecto (config rentcar.currency / DEFAULT_CURRENCY = USD).
 * Ver docs/15_AI_WORK_LOG.md (decisión "todo USD").
 */
return new class extends Migration
{
    public function up(): void
    {
        $currency = config('rentcar.currency', 'USD');

        Schema::table('vehicles', function (Blueprint $table) use ($currency) {
            $table->char('currency', 3)->default($currency)->change();
        });

        Schema::table('reservations', function (Blueprint $table) use ($currency) {
            $table->char('currency', 3)->default($currency)->change();
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->char('currency', 3)->default('DOP')->change();
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->char('currency', 3)->default('DOP')->change();
        });
    }
};
