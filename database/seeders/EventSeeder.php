<?php

namespace Database\Seeders;

use App\Models\Event;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Event::firstOrCreate([
            'name' => 'Event 1',
            'date' => '2024-05-24',
            'location' => 'Location 1',
            'description' => 'Description 1',
            'max_participants' => 100,
            'image' => 'image1.jpg',
            'is_canceled' => false,
            'user_id' => 2,
        ]);

        Event::firstOrCreate([
            'name' => 'Event 2',
            'date' => '2024-05-25',
            'location' => 'Location 2',
            'description' => 'Description 2',
            'max_participants' => 200,
            'image' => 'image2.jpg',
            'is_canceled' => false,
            'user_id' => 1,
        ]);

        Event::firstOrCreate([
            'name' => 'Event 3',
            'date' => '2024-05-26',
            'location' => 'Location 3',
            'description' => 'Description 3',
            'max_participants' => 300,
            'image' => 'image3.jpg',
            'is_canceled' => true,
            'user_id' => 1,
        ]);

    }
}
