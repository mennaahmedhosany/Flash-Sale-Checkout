<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('products')->insert([
            'name'            => 'Flash Sale Item',
            'description'     => 'Limited stock item for flash sale.',
            'price'           => 99.99,
            'stock_available' => 1000,   // your initial stock
            'stock_reserved'  => 0,
            'version'         => 1,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);
    }
}
