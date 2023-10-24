<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TransactionChannel;

class TransactionChannelsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $channels = ["arena", "consumus", "proxima", "topbrain"];
        $channels = [
            ['product' => 'arena', 'logo' => 'https://one-audience-campaign.s3-eu-west-1.amazonaws.com/Arena-Logo-01.png'],
            ['product' => 'consumus', 'logo' => null],
            ['product' => 'proxima', 'logo' => null],
            ['product' => 'topbrain', 'logo' => null],
        ];

        foreach ($channels as $channel) {
            TransactionChannel::updateOrCreate(['name' => $channel['product']], ['logo' => $channel['logo']]);
        }
    }
}
