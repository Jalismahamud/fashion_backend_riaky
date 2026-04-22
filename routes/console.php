<?php


use Illuminate\Support\Facades\Schedule;


Schedule::command('users:check-alive')->everyMinute();

Schedule::command('notify:activity-notification')->everyMinute();

