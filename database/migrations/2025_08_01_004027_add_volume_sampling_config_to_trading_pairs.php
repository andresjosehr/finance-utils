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
        Schema::table('trading_pairs', function (Blueprint $table) {
            $table->json('volume_ranges')->nullable()->after('collection_config')
                ->comment('Array of volume amounts to sample for better price distribution');
            $table->boolean('use_volume_sampling')->default(false)->after('volume_ranges')
                ->comment('Whether to use multi-volume sampling strategy');
            $table->decimal('default_sample_volume', 10, 2)->default(500.00)->after('use_volume_sampling')
                ->comment('Default volume for single-point sampling when not using ranges');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trading_pairs', function (Blueprint $table) {
            $table->dropColumn(['volume_ranges', 'use_volume_sampling', 'default_sample_volume']);
        });
    }
};
