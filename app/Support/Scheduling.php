<?php

namespace App\Support;

/**
 * Presentation helpers for the scheduling rules in config/scheduling.php. The rules
 * themselves stay in config so the booking flow and the admin pages read one source; this
 * class only turns the raw numbers into the wording shown to users. It exists so the
 * controller and the booking view describe the same limits with the same words.
 */
final class Scheduling
{
    /** Required break between two events on one day, in minutes. Never negative. */
    public static function minGapMinutes(): int
    {
        return max(0, (int) config('scheduling.min_gap_minutes', 0));
    }

    /** Events allowed on one calendar day. Never negative. */
    public static function maxEventsPerDay(): int
    {
        return max(0, (int) config('scheduling.max_events_per_day', 0));
    }

    /** "2 hours", "1 hour 30 minutes", "45 minutes", or '' for zero. */
    public static function humanizeMinutes(int $minutes): string
    {
        $parts = [];
        if ($hours = intdiv($minutes, 60)) {
            $parts[] = $hours.' hour'.($hours === 1 ? '' : 's');
        }
        if ($rest = $minutes % 60) {
            $parts[] = $rest.' minute'.($rest === 1 ? '' : 's');
        }

        return implode(' ', $parts);
    }

    /** The gap as a phrase ("2 hours"), or null when no break is configured. */
    public static function minGapLabel(): ?string
    {
        $label = self::humanizeMinutes(self::minGapMinutes());

        return $label === '' ? null : $label;
    }

    /** Minutes past midnight -> "h:mm AM/PM"; the end of the day reads "11:59 PM". */
    public static function minutesToClock(int $minutes): string
    {
        $minutes = max(0, min($minutes, 1439));
        $hour = intdiv($minutes, 60) % 12;
        if ($hour === 0) {
            $hour = 12;
        }

        return $hour.':'.sprintf('%02d', $minutes % 60).' '.($minutes >= 720 ? 'PM' : 'AM');
    }
}
