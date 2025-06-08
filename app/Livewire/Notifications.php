<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Lazy;
use Livewire\Component;


class Notifications extends Component
{
    public $notifications = [];
    protected $maxNotifications = 5; // Limit the number of notifications

    protected $listeners = ['notify' => 'handleNotification'];

    public function handleNotification($data)
    {
        Log::info($data);
        // Add new notification at the beginning of the array
        array_unshift($this->notifications, [
            'message' => $data['message'],
            'type' => $data['type'],
            'id' => uniqid()
        ]);

        // Keep only the latest notifications
        if (count($this->notifications) > $this->maxNotifications) {
            array_pop($this->notifications);
        }
    }

    public function remove($notificationId)
    {
        $this->notifications = array_values(array_filter($this->notifications, function($notification) use ($notificationId) {
            return $notification['id'] !== $notificationId;
        }));
    }

    public function render()
    {
        return view('livewire.notifications');
    }
}
