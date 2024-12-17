<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\Assignment;
use App\Notifications\AssignmentDueReminderNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendAssignmentDueReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $assignment;

    public function __construct(Assignment $assignment)
    {
        $this->assignment = $assignment;
    }

    public function handle()
    {
        $students = $this->assignment->class->students;

        $studentNotSubmit = $students->filter(function ($student) {
            return !$this->assignment->submittedAssignments()->where('student_id', $student->id)->exists();
        });

        foreach ($studentNotSubmit as $student) {
            $student->notify(new AssignmentDueReminderNotification($this->assignment));
        }
    }
}
