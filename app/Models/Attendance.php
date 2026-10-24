<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * One row of tbl_attendances.
 *
 *   attendance_id (PK), user_id, role_id, attendance_at (DATE), time_in (TIME),
 *   time_out (TIME), attendance_state (varchar), logged_by (int),
 *   last_edited_by (int), last_edited_at (DATETIME), enrollment_id.
 *
 * Coach rows have role_id = 2 and no enrollment_id (logged by the admin).
 * Student / athlete rows point to their tbl_enrollments row through
 * enrollment_id (logged by a coach).
 *
 * Dates and times are intentionally NOT cast, so they stay the plain strings
 * the controllers already work with ("2026-10-11", "08:05:00").
 */
class Attendance extends Model
{
    /** Value stored in attendance_state when someone is timed in. */
    public const STATE_PRESENT = 'Present';

    /** Enrollments table the enrollment_id column points to. */
    public const ENROLLMENTS_TABLE = 'tbl_enrollments';

    protected $table = 'tbl_attendances';
    protected $primaryKey = 'attendance_id';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'role_id',
        'attendance_at',
        'time_in',
        'time_out',
        'attendance_state',
        'logged_by',
        'last_edited_by',
        'last_edited_at',
        'enrollment_id',
    ];

    /* ---------------------------------------------------------
     * Scopes
     * --------------------------------------------------------- */

    public function scopeOnDate(Builder $query, string $date): Builder
    {
        return $query->where('attendance_at', $date);
    }

    public function scopeOfRole(Builder $query, int $roleId): Builder
    {
        return $query->where('role_id', $roleId);
    }

    /** @param int|iterable<int> $userIds */
    public function scopeForUser(Builder $query, $userIds): Builder
    {
        return is_iterable($userIds)
            ? $query->whereIn('user_id', $userIds)
            : $query->where('user_id', $userIds);
    }

    /** @param int|iterable<int> $enrollmentIds */
    public function scopeForEnrollment(Builder $query, $enrollmentIds): Builder
    {
        return is_iterable($enrollmentIds)
            ? $query->whereIn('enrollment_id', $enrollmentIds)
            : $query->where('enrollment_id', $enrollmentIds);
    }

    /** Rows that actually have a Time In (a session that was attended). */
    public function scopeAttended(Builder $query): Builder
    {
        return $query->whereNotNull('time_in');
    }

    /** Earliest Time In first; rows without a Time In go last. */
    public function scopeEarliestFirst(Builder $query): Builder
    {
        return $query->orderByRaw('time_in IS NULL, time_in');
    }

    /* ---------------------------------------------------------
     * Helpers
     * --------------------------------------------------------- */

    /**
     * Columns to merge into an update/create to record who touched the row last
     * and when. logged_by is NOT touched: it stays whoever first logged the row.
     */
    public static function editStamp(int $userId): array
    {
        return [
            'last_edited_by' => $userId,
            'last_edited_at' => Carbon::now()->toDateTimeString(),
        ];
    }

    /** TIME value ("08:05:00") -> "HH:MM" (24h), or null. */
    public static function hhmm($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return Carbon::parse($value)->format('H:i');
    }
}