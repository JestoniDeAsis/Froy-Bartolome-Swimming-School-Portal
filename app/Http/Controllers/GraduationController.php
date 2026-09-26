<?php

namespace App\Http\Controllers;

use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class GraduationController extends Controller
{
    /**
     * Build the Active / Ready for Assessment / Graduated tabs data
     * used by the graduation page.
     *
     * @return array<string, array{label: string, icon: string, students: array<int, array<string, int|string|null>>}>
     */
    public function graduationTabs(): array
    {
        $lastSessions = DB::table('tbl_attendances')
            ->select('user_id')
            ->selectRaw('MAX(attendance_at) AS last_session')
            ->groupBy('user_id');

        $students = DB::table('tbl_students as s')
            ->join('tbl_users as u', 'u.user_id', '=', 's.user_id')
            ->leftJoin('tbl_user_profile as p', 'p.user_id', '=', 's.user_id')
            ->leftJoin('tbl_courses as c', 'c.course_id', '=', 's.course_id')
            ->leftJoinSub($lastSessions, 'attendance_summary', function (JoinClause $join) {
                $join->on('attendance_summary.user_id', '=', 's.user_id');
            })
            ->where('u.role_id', 3)
            ->orderBy('p.last_name')
            ->orderBy('p.first_name')
            ->select([
                's.student_id',
                's.session_count',
                's.graduation_at',
                'c.course_name',
                'c.course_session_count',
                'p.first_name',
                'p.middle_name',
                'p.last_name',
                'p.name_suffix',
                'attendance_summary.last_session',
            ])
            ->get();

        $graduationTabs = [
            'active' => ['label' => 'Active Students', 'icon' => 'bi-clock-fill', 'students' => []],
            'ready' => ['label' => 'Ready for Assessment', 'icon' => 'bi-mortarboard-fill', 'students' => []],
            'graduated' => ['label' => 'Graduated', 'icon' => 'bi-check-circle-fill', 'students' => []],
        ];

        foreach ($students as $student) {
            $middleName = trim((string) $student->middle_name);
            $middleInitial = $middleName !== '' ? mb_substr($middleName, 0, 1) . '.' : '';
            $firstAndMiddle = trim((string) $student->first_name . ' ' . $middleInitial);
            $nameParts = array_filter([
                $firstAndMiddle,
                trim((string) $student->name_suffix),
            ], fn (string $part): bool => $part !== '');
            $lastName = trim((string) $student->last_name);
            $studentName = $lastName !== ''
                ? $lastName . ', ' . implode(' ', $nameParts)
                : ($firstAndMiddle !== '' ? $firstAndMiddle : 'Unnamed student');

            $completedSessions = max(0, (int) $student->session_count);
            $requiredSessions = max(0, (int) $student->course_session_count);
            $sessionsLabel = $requiredSessions > 0
                ? $completedSessions . ' / ' . $requiredSessions . ' sessions' . ($completedSessions >= $requiredSessions ? ' completed' : '')
                : $completedSessions . ' sessions';

            $group = 'active';
            if ($student->graduation_at !== null) {
                $group = 'graduated';
                $sessionsLabel = 'Graduated on ' . Carbon::parse($student->graduation_at)->format('m / d / Y');
            } elseif ($requiredSessions > 0 && $completedSessions >= $requiredSessions) {
                $group = 'ready';
            }

            $graduationTabs[$group]['students'][] = [
                'student_id' => (int) $student->student_id,
                'name' => $studentName,
                'level' => (string) ($student->course_name ?? 'Course not assigned'),
                'sessions' => $sessionsLabel,
                'last_session' => $student->last_session !== null
                    ? Carbon::parse($student->last_session)->format('m / d / Y')
                    : null,
                'certificate' => 'Certificate not recorded',
            ];
        }

        return $graduationTabs;
    }
}