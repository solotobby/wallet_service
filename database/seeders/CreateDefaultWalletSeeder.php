<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Wallet;
use Illuminate\Support\Str;

class CreateDefaultWalletSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $revenueShareGroups = ["platform_owner", "partner"];
        foreach ($revenueShareGroups as $group) {
            Wallet::firstOrCreate([
                'revenue_share_group' => $group
            ], [
                'user_id' => (string) Str::uuid()
            ]);
        }
    }
}
