<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create(config('ai-content-generator.tables.history', 'ai_content_generations'), function (Blueprint $table) {
            $table->id();
            $table->string('driver');
            $table->string('model')->nullable();
            $table->longText('prompt');
            $table->longText('system_instruction')->nullable();
            $table->json('payload')->nullable();
            $table->string('response_format')->default('text');
            $table->string('output_type')->default('single');
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->decimal('temperature', 5, 2)->nullable();
            $table->unsignedInteger('max_tokens')->nullable();
            $table->string('status')->default('pending');
            $table->string('error_code')->nullable();
            $table->text('error_message')->nullable();
            $table->longText('raw_response')->nullable();
            $table->json('parsed_response')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->unsignedInteger('prompt_tokens')->nullable();
            $table->unsignedInteger('completion_tokens')->nullable();
            $table->unsignedInteger('total_tokens')->nullable();
            $table->timestamps();

            $table->index(['driver', 'status']);
            $table->index('created_at');
        });

        Schema::create(config('ai-content-generator.tables.providers', 'ai_provider_statuses'), function (Blueprint $table) {
            $table->id();
            $table->string('driver')->unique();
            $table->string('status')->default('available');
            $table->string('error_code')->nullable();
            $table->text('message')->nullable();
            $table->timestamp('last_error_at')->nullable();
            $table->timestamp('blocked_until')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('ai-content-generator.tables.providers', 'ai_provider_statuses'));
        Schema::dropIfExists(config('ai-content-generator.tables.history', 'ai_content_generations'));
    }
};
