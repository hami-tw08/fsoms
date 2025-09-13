<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DeliveryArea;

class DeliveryAreaSeeder extends Seeder
{
    public function run(): void
    {
        $areas = ['浪江','双葉','大熊','小高区'];
        foreach ($areas as $name) {
            DeliveryArea::updateOrCreate(['name'=>$name], ['is_active'=>true]);
        }
    }
}
