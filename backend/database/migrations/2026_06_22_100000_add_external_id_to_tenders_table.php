<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenders', function (Blueprint $table) {
            $table->string('external_id')->nullable()->after('source');
            $table->timestamp('imported_at')->nullable()->after('external_id');
            $table->unique(['source', 'external_id'], 'tenders_source_external_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('tenders', function (Blueprint $table) {
            $table->dropUnique('tenders_source_external_id_unique');
            $table->dropColumn(['external_id', 'imported_at']);
        });
    }
};
