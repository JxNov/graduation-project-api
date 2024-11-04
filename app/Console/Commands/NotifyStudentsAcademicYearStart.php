<?php

namespace App\Console\Commands;

use App\Jobs\NotifyStudentsAcademicYearStartJob;
use App\Models\AcademicYear;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;

class NotifyStudentsAcademicYearStart extends Command
{
    protected $signature = 'app:notify-students-academic-year-start';

    protected $description = 'Remind students about the upcoming academic year';

    public function handle()
    {
        $currentDate = Carbon::now();
        $halfMonthLater = $currentDate->copy()->addDays(15);

        $academicYear = AcademicYear::where('start_date', '>=', $currentDate)
            ->where('start_date', '<=', $halfMonthLater)
            ->first();

        if (!$academicYear) {
            $this->info('Không có năm học nào bắt đầu trong vòng 15 ngày tới');
            return;
        }

        User::whereHas('academicYears', function ($query) use ($academicYear) {
            $query->where('academic_year_id', $academicYear->id);
        })->chunk(100, function ($students) use ($academicYear) {
            foreach ($students as $student) {
                NotifyStudentsAcademicYearStartJob::dispatch($student, $academicYear);
            }
        });

        $this->info('Đã gửi thông báo nhắc nhở cho các học sinh cho năm học sắp tới');
    }
}
