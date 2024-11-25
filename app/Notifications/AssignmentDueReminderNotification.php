<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;

class AssignmentDueReminderNotification extends Notification
{
    use Queueable;

    protected $assignment;

    public function __construct($assignment)
    {
        $this->assignment = $assignment;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'notifyTitle' => 'Bài tập sắp đến hạn!',
            'assignmentTitle' => $this->assignment->title,
            'assignmentSlug' => $this->assignment->slug,
            'dueDate' => $this->assignment->due_date
        ];
    }
}
