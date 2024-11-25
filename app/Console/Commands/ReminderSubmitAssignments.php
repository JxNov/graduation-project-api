<?php

namespace App\Console\Commands;

use App\Jobs\SendAssignmentDueReminderJob;
use App\Models\Assignment;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ReminderSubmitAssignments extends Command
{
    protected $signature = 'app:reminder-submit-assignments';

    protected $description = 'Nhắc nhở học sinh nộp bài tập';

    public function handle()
    {
        $assignments = Assignment::where('due_date', '>', Carbon::now())
            ->where('due_date', '<=', Carbon::now()->addDay())
            ->get();

        foreach ($assignments as $assignment) {
            SendAssignmentDueReminderJob::dispatch($assignment);
        }

        $this->info('Đã gửi thông báo đến học sinh');
    }
}
