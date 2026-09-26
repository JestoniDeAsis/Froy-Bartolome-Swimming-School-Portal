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
            'announcement_recipients_id' => ['required', 'integer', Rule::in([1, 2, 3, 4])],
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
                'announcement' => $announcement->fresh([
                    'announcement_id',
                    'announcement_title',
                    'announcement_message',
                    'announcement_posted_at',
                    'announcement_updated_at',
                    'announcement_recipients_id',
                ]),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}