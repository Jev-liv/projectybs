<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KernelBobotConfigSeeder extends Seeder
{
    public function run(): void
    {
        $configs = [
            [
                'jenis'     => 'Claybath',
                'direction' => 'asc',
                'limit_100' => 0.7,
                'limit_90'  => 0.8,
                'limit_80'  => 0.9,
                'limit_70'  => 1.0,
                'limit_60'  => 0.99,
                'limit_50'  => 1.0,
            ],
            [
                'jenis'     => 'Fibercyclone',
                'direction' => 'asc',
                'limit_100' => 1.24,
                'limit_90'  => 1.29,
                'limit_80'  => 1.34,
                'limit_70'  => 1.4,
                'limit_60'  => 1.39,
                'limit_50'  => 1.4,
            ],
            [
                'jenis'     => 'LTDS',
                'direction' => 'asc',
                'limit_100' => 0.8,
                'limit_90'  => 0.9,
                'limit_80'  => 1.0,
                'limit_70'  => 1.1,
                'limit_60'  => 1.09,
                'limit_50'  => 1.1,
            ],
            [
                'jenis'     => 'Inlet Kernel Silo',
                'direction' => 'asc',
                'limit_100' => 6.7,
                'limit_90'  => 6.9,
                'limit_80'  => 7.1,
                'limit_70'  => 7.25,
                'limit_60'  => 7.24,
                'limit_50'  => 7.4,
            ],
            [
                'jenis'     => 'Outlet Kernel Silo',
                'direction' => 'asc',
                'limit_100' => 6.7,
                'limit_90'  => 6.9,
                'limit_80'  => 7.1,
                'limit_70'  => 7.25,
                'limit_60'  => 7.24,
                'limit_50'  => 7.4,
            ],
            [
                'jenis'     => 'Ripple Mill',
                'direction' => 'desc',
                'limit_100' => 99.00,
                'limit_90'  => 98.00,
                'limit_80'  => 97.00,
                'limit_70'  => 96.00,
                'limit_60'  => 95.00,
                'limit_50'  => 94.00,
            ],
            [
                'jenis'     => 'CaCo3',
                'direction' => 'asc',
                'limit_100' => 0,
                'limit_90'  => 2.00,
                'limit_80'  => 2.50,
                'limit_70'  => 3.00,
                'limit_60'  => 3.50,
                'limit_50'  => 4.00,
            ],
            [
                'jenis'     => 'Press',
                'direction' => 'asc',
                'limit_100' => 0,
                'limit_90'  => 10.10,
                'limit_80'  => 15.00,
                'limit_70'  => 20.00,
                'limit_60'  => 25.00,
                'limit_50'  => 35.10,
            ],
        ];

        DB::table('kernel_bobot_configs')
            ->whereIn('jenis', ['Outlet Kernel Silo Moist', 'Press Moist'])
            ->delete();

        DB::table('kernel_bobot_configs')->upsert(
            $configs,
            ['jenis'],
            ['direction', 'limit_100', 'limit_90', 'limit_80', 'limit_70', 'limit_60', 'limit_50']
        );
    }
}
