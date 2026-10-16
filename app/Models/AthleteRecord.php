<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * One personal record (PR) of an athlete: a time for one event
 * (stroke + distance). Every row is kept, so the rows of an athlete are
 * their history. The current record of an event is the fastest row that
 * has not been removed.
 *
 * Times are stored in hundredths of a second (time_cs): 31.25 s = 3125.
 */
class AthleteRecord extends Model
{
    /** The four main strokes (key => label). Not editable from the app for now. */
    public const STROKES = [
        'freestyle'    => 'Freestyle',
        'backstroke'   => 'Backstroke',
        'breaststroke' => 'Breaststroke',
        'butterfly'    => 'Butterfly',
    ];

    /** Distances in metres that can be recorded. Add more here if needed. */
    public const DISTANCES = [50, 100, 200];

    protected $table = 'tbl_athlete_records';

    protected $primaryKey = 'record_id';

    // created_at is set by the database default.
    public $timestamps = false;

    protected $fillable = [
        'athlete_id',
        'stroke',
        'distance_m',
        'time_cs',
        'recorded_on',
        'recorded_by',
        'notes',
        'removed_at',
        'removed_by',
    ];

    protected $casts = [
        'time_cs' => 'integer',
        'distance_m' => 'integer',
    ];

    /** Rows that have not been removed. */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('removed_at');
    }

    public static function eventLabel(string $stroke, int $distance): string
    {
        return $distance . ' m ' . (self::STROKES[$stroke] ?? ucfirst($stroke));
    }

    /** 3125 -> "31.25", 6530 -> "1:05.30" */
    public static function formatTime(int $cs): string
    {
        $minutes = intdiv($cs, 6000);
        $seconds = intdiv($cs % 6000, 100);
        $hundredths = $cs % 100;

        $head = $minutes > 0
            ? $minutes . ':' . str_pad((string) $seconds, 2, '0', STR_PAD_LEFT)
            : (string) $seconds;

        return $head . '.' . str_pad((string) $hundredths, 2, '0', STR_PAD_LEFT);
    }

    /**
     * "31.25", "31", "31.2", "1:05.30", "1:05" -> hundredths of a second.
     * Returns null when the text is not a valid time (or is zero).
     */
    public static function parseTime(string $text): ?int
    {
        if (! preg_match('/^(?:(\d{1,2}):)?(\d{1,2})(?:\.(\d{1,2}))?$/', trim($text), $m)) {
            return null;
        }

        $minutes = (int) ($m[1] !== '' ? $m[1] : 0);
        $seconds = (int) $m[2];
        $hundredths = isset($m[3]) && $m[3] !== '' ? (int) str_pad($m[3], 2, '0') : 0;

        if ($seconds > 59) {
            return null;
        }

        $total = ($minutes * 60 + $seconds) * 100 + $hundredths;

        return $total > 0 ? $total : null;
    }
}