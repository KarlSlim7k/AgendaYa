<?php

use App\Jobs\SendAppointmentReminders;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Enviar recordatorios de citas 24h antes
Schedule::job(new SendAppointmentReminders)
    ->hourly()
    ->description('Enviar recordatorios de citas próximas (24h)');
