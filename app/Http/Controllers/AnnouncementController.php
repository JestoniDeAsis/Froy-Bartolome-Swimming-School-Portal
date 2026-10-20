<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Throwable;

class AnnouncementController extends Controller
{
    /**
     * Replaces get_announcements.php. The page itself is rendered by
     * HomeController::navigate(); this only serves the JSON data.
     */
    public function index()
    {
        try {
            $announcements = Announcement::query()
                ->orderByDesc('announcement_posted_at')
                ->get([
                    'announcement_id',
                    'announcement_title',
                    'announcement_message',
                    'announcement_posted_at',
                    'announcement_updated_at',
                    'announcement_recipients_id',
                ]);

            return response()->json([
                'success' => true,
                'announcements' => $announcements,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Read-only feed for regular users: only announcements meant for
     * "Everyone" plus whatever the logged-in user's own role is.
     * Replaces having the client fetch and filter the full admin list.
     */
    public function feed(Request $request)
    {
        try {
            $roleId = $this->recipientIdForUser($request->user());

            $announcements = Announcement::query()
                ->whereIn('announcement_recipients_id', array_unique([1, $roleId]))
                ->orderByDesc('announcement_posted_at')
                ->get([
                    'announcement_id',
                    'announcement_title',
                    'announcement_message',
                    'announcement_posted_at',
                    'announcement_updated_at',
                    'announcement_recipients_id',
                ]);

            return response()->json([
                'success' => true,
                'announcements' => $announcements,
                'viewer_recipient_id' => $roleId,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Maps the logged-in user to an announcement_recipients_id (2=Coaches,
     * 3=Students, 4=Athletes, 5=Applicants). ADJUST THIS to match your actual User model's
     * role column/values — this is a guess based on the FBSS Admin/Coach/
     * Swimmer module split.
     */
    /**
     * announcement_recipients_id and the user's role id share the same
     * numbering (2=Coach, 3=Student, 4=Athlete, 5=Applicant) — role id 1 is Admin, which
     * has no dedicated audience and falls back to "Everyone" (1) instead.
     * ADJUST "role_id" below if your User model names this column differently.
     */
    private function recipientIdForUser($user): int
    {
        $roleId = (int) ($user->role_id ?? 0);

        return in_array($roleId, [2, 3, 4, 5], true) ? $roleId : 1;
    }

    /**
     * Replaces save_announcement.php. Creates a new announcement when no
     * announcement_id is supplied, otherwise updates the existing row —
     * same behavior as the original endpoint.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'announcement_id' => ['nullable', 'integer', 'exists:tbl_announcements,announcement_id'],
            'announcement_title' => ['required', 'string', 'max:100'],
            'announcement_message' => ['required', 'string', 'max:1000'],
            'announcement_recipients_id' => ['required', 'integer', Rule::in([1, 2, 3, 4, 5])],
        ], [
            'announcement_id.exists' => 'A valid announcement_id is required.',
            'announcement_title.required' => 'A title is required.',
            'announcement_message.required' => 'A message is required.',
            'announcement_recipients_id.required' => 'A valid recipient is required.',
            'announcement_recipients_id.in' => 'A valid recipient is required.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 400);
        }

        $data = $validator->validated();

        try {
            if (!empty($data['announcement_id'])) {
                $announcement = Announcement::findOrFail($data['announcement_id']);
                $announcement->update([
                    'announcement_title' => $data['announcement_title'],
                    'announcement_message' => $data['announcement_message'],
                    'announcement_recipients_id' => $data['announcement_recipients_id'],
                ]);
            } else {
                $announcement = Announcement::create([
                    'announcement_title' => $data['announcement_title'],
                    'announcement_message' => $data['announcement_message'],
                    'announcement_recipients_id' => $data['announcement_recipients_id'],
                ]);
            }

            return response()->json([
                'success' => true,
                'announcement' => $announcement->fresh(),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Deletes an announcement.
     */
    public function destroy($id)
    {
        try {
            $announcement = Announcement::findOrFail($id);
            $announcement->delete();

            return response()->json([
                'success' => true,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}