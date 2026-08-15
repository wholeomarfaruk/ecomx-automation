<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\State;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PoliceStationSeeder extends Seeder
{


    public function run(): void
    {


        $policestationByCity = [
            'Dhaka Metropolitan' => [
                ['name' => 'Demra', 'local_name' => 'ডেমরা'],
                ['name' => 'Kadamtali', 'local_name' => 'কদমতলী'],
                ['name' => 'Shyampur', 'local_name' => 'শ্যামপুর'],
                ['name' => 'Kotwali', 'local_name' => 'কোতোয়ালী'],
                ['name' => 'Paltan', 'local_name' => 'পল্টন'],
                ['name' => 'Ramna', 'local_name' => 'রামনা'],
                ['name' => 'Gendaria', 'local_name' => 'গেণ্ডারিয়া'],
                ['name' => 'Lalbagh', 'local_name' => 'লালবাগ'],
                ['name' => 'Kotwali', 'local_name' => 'কোতোয়ালী'],
                ['name' => 'Motijheel', 'local_name' => 'মতিঝিল'],
                ['name' => 'Ramna', 'local_name' => 'রামনা'],
                ['name' => 'Shahbagh', 'local_name' => 'শাহবাগ'],
                ['name' => 'Tejgaon', 'local_name' => 'তেজগাঁও'],
                ['name' => 'Uttara', 'local_name' => 'উত্তরা']
            ],
            'Narayanganj Sadar' => [
                ['name' => 'Narayanganj Sadar', 'local_name' => 'নারায়ণগঞ্জ সদর'],
                ['name' => 'Bandar', 'local_name' => 'বান্দর']
            ]
        ];

    }


}
