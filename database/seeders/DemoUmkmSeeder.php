<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DemoUmkmSeeder extends Seeder
{
    /**
     * Seed ready-to-use demo UMKM accounts idempotently.
     */
    public function run(): void
    {
        $accounts = [
            [
                'name' => 'Sari Rahmawati',
                'business_name' => 'Dapur Bu Sari',
                'slug' => 'dapur-bu-sari',
                'email' => 'sari@inamikro.id',
                'password' => 'password',
                'role' => 'umkm',
            ],
            [
                'name' => 'Budi Santoso',
                'business_name' => 'Sigap Teknik',
                'slug' => 'sigap-teknik',
                'email' => 'budi@inamikro.id',
                'password' => 'password',
                'role' => 'umkm',
            ],
        ];

        foreach ($accounts as $account) {
            User::updateOrCreate(
                ['email' => $account['email']],
                $account,
            );
        }
    }
}
