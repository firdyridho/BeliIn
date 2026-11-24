<?php

use Illuminate\Support\Facades\Schedule;

// Automatically release expired stock reservations every 5 minutes
Schedule::command('inventory:release-expired')->everyFiveMinutes();
