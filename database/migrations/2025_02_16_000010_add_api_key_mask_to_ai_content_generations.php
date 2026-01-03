<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $table = config('ai-content-generator.tables.history', 'ai_content_generations');

        Schema::table($table, function (Blueprint $table) {
            $table->string('api_key_mask')->nullable()->after('model');
        });
    }

    public function down(): void
    {
        $table = config('ai-content-generator.tables.history', 'ai_content_generations');

        Schema::table($table, function (Blueprint $table) {
            $table->dropColumn('api_key_mask');
        });
    }
};
