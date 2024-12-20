<?php
namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;

class NewAssignmentNotification extends Notification
{
    protected $assignment;

    public function __construct($assignment)
    {
        $this->assignment = $assignment;
    }

    public function via($notifiable)
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'notifyTitle' => 'Bạn có 1 bài tập mới',
            'assignmentTitle' => $this->assignment->title,
            'assignmentSlug' => $this->assignment->slug
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'notifyTitle' => 'Bạn có 1 bài tập mới',
            'assignmentTitle' => $this->assignment->title,
            'assignmentSlug' => $this->assignment->slug
        ]);
    }
}
