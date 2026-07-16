<?php

namespace App\Support;

use Carbon\Carbon;
use DateTimeInterface;

class Dates
{
    /**
     * Whole calendar days from today to $date: 0 = today, 1 = tomorrow,
     * negative = in the past.
     *
     * Uses start-of-day on both ends so the result reflects the calendar-day
     * boundary, not elapsed 24-hour periods — Carbon's diffInDays() truncates
     * a sub-24h gap to 0, which made "tomorrow" render as "Hoy".
     */
    public static function calendarDaysUntil(?DateTimeInterface $date): ?int
    {
        if ($date === null) {
            return null;
        }

        return (int) now()->startOfDay()->diffInDays(Carbon::instance($date)->startOfDay(), false);
    }
}
