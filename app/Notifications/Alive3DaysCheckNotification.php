<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class Alive3DaysCheckNotification extends Notification
{
    use Queueable;

   public function via($notifiable)
   {
       return ['database'];
   }

   public function toDatabase($notifiable)
   {
     return [
        'message'  => 'You have to check your account status. It has been 3 days since the last check.'
     ];
   }
}
