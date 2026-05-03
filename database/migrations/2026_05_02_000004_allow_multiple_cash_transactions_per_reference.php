<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cash_transactions', function (Blueprint $table) {
            $table->dropUnique('cash_transactions_reference_id_reference_type_type_unique');
            $table->index(
                ['reference_id', 'reference_type', 'type'],
                'cash_transactions_reference_lookup_index',
            );
        });
    }

    public function down(): void
    {
        Schema::table('cash_transactions', function (Blueprint $table) {
            $table->dropIndex('cash_transactions_reference_lookup_index');
            $table->unique(['reference_id', 'reference_type', 'type']);
        });
    }
};
