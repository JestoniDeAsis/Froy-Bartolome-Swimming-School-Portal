<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use App\Models\UserProfile;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        if ($blocked = $this->logoutIfNotActive($request)) {
            return $blocked;
        }

        $roleId = Auth::user()->role_id;

        $roleMenus = config('role_menus');

        if (!isset($roleMenus[$roleId])) {
            abort(403, 'Access denied: no menu configured for this role.');
        }

        $menuItems = $roleMenus[$roleId];

        $allowedPages = array_map(fn($item) => $item['view'], $menuItems);

        $page = $request->session()->get('current_page', array_key_first($allowedPages));

        if (!isset($allowedPages[$page])) {
            $page = array_key_first($allowedPages);
        }

        $profile = UserProfile::where('user_id', Auth::id())->first();

        $roleName = DB::table('tbl_roles')
            ->where('role_id', $roleId)
            ->value('role_name'); // adjust column name if different in your tbl_roles

        $viewData = [
            'menuItems' => $menuItems,
            'page' => $page,
            'pageView' => $allowedPages[$page],
            'profile' => $profile,
            'roleName' => $roleName,
        ];

        $extraData = match ($page) {
            'registers'  => (new RegistersController())->data(),
            'athletes'  => (new AdminAthleteController())->data(),
            'graduation' => ['graduation' => app(AdminGraduationController::class)->pageData()],
            'sales_and_invoice' => app(\App\Http\Controllers\SalesInvoiceController::class)->data(),
            'profile'    => (new ProfileController())->data($roleId, $profile),
            default      => [],
        };

        $viewData = array_merge($viewData, $extraData);

        return view('home.index', $viewData);
    }

    public function navigate(Request $request, string $page)
    {
        if ($blocked = $this->logoutIfNotActive($request)) {
            return $blocked;
        }

        $roleId = Auth::user()->role_id;
        $roleMenus = config('role_menus');

        if (!isset($roleMenus[$roleId])) {
            abort(403, 'Access denied: no menu configured for this role.');
        }

        $allowedPages = array_map(fn($item) => $item['view'], $roleMenus[$roleId]);

        if (isset($allowedPages[$page])) {
            $request->session()->put('current_page', $page);
        }

        return redirect()->route('home');
    }

    /**
     * Status is only checked at login, so an admin changing a status mid-session
     * would otherwise have no effect until the user logs out. Check it on every
     * page load; if the account is no longer Active, end the session and send
     * the user back to the login page with the reason.
     */
    private function logoutIfNotActive(Request $request): ?\Illuminate\Http\RedirectResponse
    {
        $user = Auth::user();

        if ((int) $user->status_id === 1) {
            return null;
        }

        $message = match ((int) $user->status_id) {
            2 => 'Your account is currently on hold. Please contact the administrator.',
            3 => 'Your account has been deactivated. Please contact the administrator.',
            default => 'Your account status does not allow login. Please contact the administrator.',
        };

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->withErrors(['login' => $message]);
    }

    /**
     * @return array<string, array{label: string, icon: string, students: array<int, array<string, int|string|null>>}>
     */
    private function graduationTabs(): array
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