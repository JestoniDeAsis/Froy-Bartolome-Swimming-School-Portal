<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesAttendanceData;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Student > Attendance (view only).
 *
 * Returns the signed-in student's OWN enrollments and attendance logs.
 * Nothing here accepts a user id from the request, so a student can never
 * see anybody else's attendance.
 */
class StudentAttendanceController extends Controller
{
    use HandlesAttendanceData;

    /** Roles that have the Attendance page in config/role_menus.php (3 and 5 = student). */
    public const STUDENT_ROLES = [3, 5];

    /* =========================================================
     * GET /student/attendance/data
     * ========================================================= */
    public function data(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user || !in_array((int) ($user->{CoachController::ROLE_COL} ?? 0), self::STUDENT_ROLES, true)) {
            return $this->fail('You are not allowed to do that.', 403);
        }

        $userId = (int) Auth::id();

        $enrollments = DB::table(Attendance::ENROLLMENTS_TABLE . ' as e')
            ->leftJoin(CoachController::COURSES_TABLE . ' as c', 'c.' . CoachController::COURSE_ID_COL, '=', 'e.course_id')
            ->where('e.user_id', $userId)
            ->orderByDesc('e.enrollment_at')
            ->get([
                'e.enrollment_id as id',
                'e.scheduled_time',
                'e.enrollment_at',
                'e.graduation_at',
                'c.' . CoachController::COURSE_NAME_COL . ' as course_name',
                'c.course_session_count',
            ]);

        // Sessions completed (rows with a Time In) and the last one, per enrollment.
        $summary = $enrollments->isEmpty()
            ? collect()
            : Attendance::forEnrollment($enrollments->pluck('id'))
                ->attended()
                ->groupBy('enrollment_id')
                ->selectRaw('enrollment_id, COUNT(*) as total, MAX(attendance_at) as last_at')
                ->get()
                ->keyBy('enrollment_id');

        $courseNames = $enrollments->pluck('course_name', 'id');

        $logs = Attendance::forUser($userId)
            ->orderByDesc('attendance_at')
            ->orderByRaw('time_in IS NULL')
            ->orderByDesc('time_in')
            ->limit(1000)
            ->get(['attendance_id', 'attendance_at', 'time_in', 'time_out', 'attendance_state', 'logged_by', 'enrollment_id']);

        $loggerNames = $this->loggerNames($logs->pluck('logged_by'));

        $active = $enrollments->first(fn ($e) => $e->graduation_at === null);

        return response()->json([
            'success' => true,
            'default_enrollment_id' => $active ? (int) $active->id : null,
            'enrollments' => $enrollments->map(function ($e) use ($summary) {
                $start = $e->enrollment_at ? Carbon::parse($e->enrollment_at)->format('M d, Y') : '?';
                $end = $e->graduation_at
                    ? 'Graduated ' . Carbon::parse($e->graduation_at)->format('M d, Y')
                    : 'Present';
                $row = $summary->get($e->id);

                return [
                    'id' => (int) $e->id,
                    'label' => ($e->course_name ?? 'Course not assigned') . " ($start - $end)",
                    'course' => $e->course_name,
                    'schedule' => $e->scheduled_time,
                    'graduated' => $e->graduation_at !== null,
                    'done' => (int) ($row->total ?? 0),
                    'required' => max(0, (int) $e->course_session_count),
                    'last_attended' => $row->last_at ?? null,
                ];
            })->values(),
            'logs' => $logs->map(fn ($l) => [
                'id' => (int) $l->attendance_id,
                'enrollment_id' => $l->enrollment_id !== null ? (int) $l->enrollment_id : null,
                'date' => (string) $l->attendance_at,
                'course' => $l->enrollment_id !== null ? ($courseNames[$l->enrollment_id] ?? null) : null,
                'time_in' => $this->hhmm($l->time_in),
                'time_out' => $this->hhmm($l->time_out),
                'state' => $l->attendance_state,
                'logged_by' => $loggerNames[$l->logged_by] ?? null,
            ])->values(),
        ]);
    }
}