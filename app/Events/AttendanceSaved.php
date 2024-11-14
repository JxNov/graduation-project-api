<?php
namespace App\Events;

use App\Models\Attendance;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AttendanceSaved implements ShouldBroadcast, ShouldQueue
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $attendance;

    public function __construct(Attendance $attendance)
    {
        $this->attendance = $attendance;
    }

    public function broadcastOn()
    {
        return new Channel('attendance.' . $this->attendance->class_id);
    }

    public function broadcastWith()
    {
        return [
            'attendanceId' => $this->attendance->id,
            'date' => $this->attendance->date,
            'shift' => $this->attendance->shifts,
            'className' => $this->attendance->class->name,
        ];
    }
}
