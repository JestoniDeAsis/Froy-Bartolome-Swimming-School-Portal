<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\HeaderUtils;

/**
 * GET /certificates/{enrollment}
 * Opens the graduation certificate PDF, which is stored in the database
 * (tbl_enrollment_certificates). It can only be read through here:
 *   - the admin can open any certificate
 *   - a student can open their own, once they have graduated
 */
class CertificateController extends Controller
{
    private const ADMIN_ROLE_ID = 1;

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

        return response($certificate->file_data, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_INLINE, $name, $fallback),
        ]);
    }
}