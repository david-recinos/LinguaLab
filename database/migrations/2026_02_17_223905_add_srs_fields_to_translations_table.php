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
        Schema::table('translations', function (Blueprint $table) {
            $table->decimal('ease_factor', 3, 2)->default(2.50)->after('pronunciation');
            $table->unsignedInteger('interval_days')->default(1)->after('ease_factor');
            $table->timestamp('next_review_at')->nullable()->after('interval_days');
            $table->timestamp('last_reviewed_at')->nullable()->after('next_review_at');
            $table->unsignedInteger('total_reviews')->default(0)->after('last_reviewed_at');
            $table->unsignedInteger('successful_reviews')->default(0)->after('total_reviews');

            // Index for efficient due-for-review queries
            $table->index(['user_id', 'next_review_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('translations', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'next_review_at']);
            $table->dropColumn([
                'ease_factor',
                'interval_days',
                'next_review_at',
                'last_reviewed_at',
                'total_reviews',
                'successful_reviews',
            ]);
        });
    }
};
