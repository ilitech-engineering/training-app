<?php
use Illuminate\Support\Facades\Schedule;

Schedule::command('automations:run')->everyMinute()->withoutOverlapping();
