<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use App\Models\Classes;
use Illuminate\Console\Command;

class CreateDailyAttendance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-daily-attendance';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $classes = Classes::all();
        $shifts = ['Morning', 'Afternoon'];

        foreach ($classes as $class) {
            foreach ($shifts as $shift) {
                Attendance::create([
                    'date' => now(),
                    'shifts' => $shift,
                    'class_id' => $class->id,
                ]);
            }
        }

        $this->info('Daily attendance created successfully!');
    }
}
