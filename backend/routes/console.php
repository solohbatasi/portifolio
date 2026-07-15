<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('coffee-payments:reconcile --limit=50')->everyMinute()->withoutOverlapping();
