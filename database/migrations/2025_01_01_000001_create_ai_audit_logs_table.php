<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider')->default('nvidia');
            $table->string('feature')->default('distractors'); // distractors, examples, etc.
            $table->string('model')->nullable();
            $table->text('prompt')->nullable();
            $table->text('response')->nullable();
            $table->json('parsed_result')->nullable();
            $table->boolean('success')->default(false);
            $table->integer('tokens_used')->nullable();
            $table->integer('response_time_ms')->nullable();
            $table->string('error_message')->nullable();
            $table->string('translation_id')->nullable(); // Related translation if applicable
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['provider', 'feature']);
            $table->index('success');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_audit_logs');
    }
};
