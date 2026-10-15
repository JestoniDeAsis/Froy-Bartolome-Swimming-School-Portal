<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\HeaderUtils;

/**
 * Graduation certificates. The PDF is stored in the database
 * (tbl_enrollment_certificates) and can only be read through here:
 *   - the admin can open any certificate
 *   - a student can open their own, once they have graduated
 */
class CertificateController extends Controller
{
    private const ADMIN_ROLE_ID = 1;

    /**
     * Data for the student Certificates page (certificates_blade).
     * Only graduated enrollments that have an uploaded certificate are listed,
     * newest first. The PDF itself is not loaded here, just its details.
     */
    public function pageData(User $user): array
    {
        $certificates = DB::table('tbl_enrollment_certificates as cert')
            ->join('tbl_enrollments as e', 'e.enrollment_id', '=', 'cert.enrollment_id')
            ->leftJoin('tbl_courses as c', 'c.course_id', '=', 'e.course_id')
            ->where('e.user_id', $user->getKey())
            ->whereNotNull('e.graduation_at')
            ->orderByDesc('e.graduation_at')
            ->get(['e.enrollment_id', 'e.enrollment_at', 'e.graduation_at', 'c.course_name', 'cert.file_name']);

        $day = fn ($value) => $value ? Carbon::parse($value)->format('M d, Y') : null;

        return [
            'certificates' => $certificates->map(fn ($row) => [
                'id'          => (int) $row->enrollment_id,
                'course'      => $row->course_name ?? 'Course',
                'enrolledAt'  => $day($row->enrollment_at),
                'graduatedAt' => $day($row->graduation_at),
                'fileName'    => $row->file_name,
            ])->all(),
        ];
    }

    /**
     * GET /certificates/{enrollment}            opens the PDF in the browser
     * GET /certificates/{enrollment}?download=1 downloads it
     */
    public function show(Request $request, int $enrollment)
    {
        $user = $request->user();
        $row  = Enrollment::find($enrollment);

        abort_unless($row, 404);

        $isAdmin = (int) $user->role_id === self::ADMIN_ROLE_ID;
        $isOwner = (int) $row->user_id === (int) $user->getKey() && $row->graduation_at !== null;

        abort_unless($isAdmin || $isOwner, 403);

        $certificate = DB::table('tbl_enrollment_certificates')
            ->where('enrollment_id', $enrollment)
            ->first(['file_name', 'file_data']);

        abort_unless($certificate, 404);

        $name     = $certificate->file_name ?: 'certificate.pdf';
        $fallback = str_replace(['%', '/', '\\', '"'], '', Str::ascii($name)) ?: 'certificate.pdf';

        $disposition = $request->boolean('download')
            ? HeaderUtils::DISPOSITION_ATTACHMENT
            : HeaderUtils::DISPOSITION_INLINE;

        return response($certificate->file_data, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => HeaderUtils::makeDisposition($disposition, $name, $fallback),
        ]);
    }
}