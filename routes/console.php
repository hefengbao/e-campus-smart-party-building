<?php

use App\Jobs\MeetingReminderJob;
use App\Jobs\PushBirthdayJob;
use App\Jobs\ScanAlertsJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// 监督预警扫描：每日 06:00。
Schedule::job(new ScanAlertsJob)->dailyAt('06:00')->name('scan-alerts');

// 政治生日推送：每日 08:00。
Schedule::job(new PushBirthdayJob)->dailyAt('08:00')->name('push-birthday');

// 三会一课填报提醒：每周一 09:00。
Schedule::job(new MeetingReminderJob)->weeklyOn(1, '09:00')->name('meeting-reminder');
