<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class NotifyStudentsAcademicYearStartJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $student;
    public $academicYear;

    public function __construct($student, $academicYear)
    {
        $this->student = $student;
        $this->academicYear = $academicYear;
    }

    public function handle(): void
    {
        Mail::send(
            'emails.notify_student_academic_year_start',
            [
                'student' => $this->student,
                'academicYear' => $this->academicYear
            ],
            function ($message) {
                $message->to($this->student->email)
                    ->subject('Thông báo năm học mới');
            }
        );
    }
}
