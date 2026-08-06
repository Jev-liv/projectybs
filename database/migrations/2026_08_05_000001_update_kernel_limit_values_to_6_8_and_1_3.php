<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('kernel_master_data')
            ->where('kode', 'like', 'FC%')
            ->update([
                'limit_operator' => 'le',
                'limit_value' => 1.300,
            ]);

        DB::table('kernel_master_data')
            ->where('kode', 'like', 'IN%')
            ->orWhere('kode', 'like', 'OUT%')
            ->update([
                'limit_operator' => 'lt',
                'limit_value' => 6.800,
            ]);

        DB::table('kernel_dirt_moist_calculations')
            ->where('kode', 'like', 'IN%')
            ->update([
                'dirty_limit_operator' => 'lt',
                'dirty_limit_value' => 6.800,
            ]);

        DB::table('kernel_dirt_moist_calculations')
            ->where('kode', 'like', 'OUT%')
            ->update([
                'moist_limit_operator' => 'lt',
                'moist_limit_value' => 6.800,
            ]);
    }

    public function down(): void
    {
        DB::table('kernel_master_data')
            ->where('kode', 'like', 'FC%')
            ->update([
                'limit_operator' => 'le',
                'limit_value' => 1.300,
            ]);

        DB::table('kernel_master_data')
            ->where('kode', 'like', 'IN%')
            ->orWhere('kode', 'like', 'OUT%')
            ->update([
                'limit_operator' => 'le',
                'limit_value' => 6.800,
            ]);

        DB::table('kernel_dirt_moist_calculations')
            ->where('kode', 'like', 'IN%')
            ->update([
                'dirty_limit_operator' => 'le',
                'dirty_limit_value' => 6.800,
            ]);

        DB::table('kernel_dirt_moist_calculations')
            ->where('kode', 'like', 'OUT%')
            ->update([
                'moist_limit_operator' => 'le',
                'moist_limit_value' => 6.800,
            ]);
    }
};