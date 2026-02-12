<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('source_language_id')->constrained('languages')->cascadeOnDelete();
            $table->foreignId('target_language_id')->constrained('languages')->cascadeOnDelete();
            $table->enum('type', ['word', 'text', 'expression']);
            $table->foreignId('word_type_id')->nullable()->constrained()->nullOnDelete();
            $table->text('source_text');
            $table->text('target_text');
            $table->text('example_sentence')->nullable();
            $table->text('notes')->nullable();
            $table->string('pronunciation', 500)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'source_language_id', 'target_language_id', 'type'], 'trans_user_src_tgt_type_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('translations');
    }
};
