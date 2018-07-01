<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $starting_codes = [0, '+44'];

        $product_codes = [
            '123456',
            'This is a test',
            'abc',
            'phone products Samsung',
            'Samsung affiliates',
            'O2 Samsung affiliate',
            'O2 mobile network'
        ];

        $msisdn_numbers = [];

        for($j = 0; $j < 50; $j++)
        {
            $msisdn_numbers[] = $starting_codes[rand(0, 1)] . rand(7000000000,7999999999);
        }

        $max_products_index = sizeof($product_codes) - 1;
        $max_msisdn_index = sizeof($msisdn_numbers) - 1;
        $start_time = time();

        while(time() < $start_time + (60 * 10))
        {
            DB::table('pm_subscriptions')
                ->insert([
                    'msisdn' => $msisdn_numbers[rand(0, $max_msisdn_index)],
                    'product_id' => $product_codes[rand(0, $max_products_index)],
                    'created_at' => date('Y-m-d H:i:s')
                ]);
        }
    }
}
