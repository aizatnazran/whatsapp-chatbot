<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AppointmentSeeder extends Seeder
{
    public function run(): void
    {
        $baseDate = Carbon::tomorrow();
        
        $appointments = [];
        
        // Create appointments for each user 
        for ($userId = 1; $userId <= 5; $userId++) {
            // Create 3 appointments per user
            for ($i = 0; $i < 3; $i++) {
                $appointmentDate = $baseDate->copy()->addDays($i);
                
                // Create appointments at different times (9 AM, 2 PM, 4 PM)
                $times = ['09:00:00', '14:00:00', '16:00:00'];
                
                $appointments[] = [
                    'user_id' => $userId,
                    'appointment_date' => $appointmentDate->format('Y-m-d'),
                    'appointment_time' => $times[array_rand($times)],
                    'status' => array_rand(['scheduled' => 0, 'completed' => 1, 'cancelled' => 2]),
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ];
            }
        }

        DB::table('appointments')->insert($appointments);
    }
}
