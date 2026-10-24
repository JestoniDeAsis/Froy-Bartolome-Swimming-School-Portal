<?php

namespace App\Http\Controllers;

use App\Models\AthleteRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Athlete-side dashboard data: own records, rankings, history and leaderboard.
 * This controller exposes no record-management endpoints.
 */
class AthleteDashboardController extends Controller
{
    private const ATHLETE_ROLE_ID = 4;
    private const FORMER_ATHLETE_ROLE_ID = 6;

    private function isAthlete($user): bool
    {
        return $user && in_array(
            (int) ($user->{CoachController::ROLE_COL} ?? 0),
            [self::ATHLETE_ROLE_ID, self::FORMER_ATHLETE_ROLE_ID],
            true
        );
    }

    private function denyUnlessAthlete(Request $request): ?JsonResponse
    {
        return $this->isAthlete($request->user())
            ? null
            : response()->json(['success' => false, 'message' => 'Only athletes can view this.'], 403);
    }

    private function fail(string $message, int $status = 422): JsonResponse
    {
        return response()->json(['success' => false, 'message' => $message], $status);
    }

    /* ---------------------------------------------------------
     * GET /athlete/records/leaderboard?stroke=&distance=
     * Fastest time of each athlete in one event, fastest first.
     * Equal times share a rank.
     * --------------------------------------------------------- */
    public function leaderboard(Request $request): JsonResponse
    {
        if ($denied = $this->denyUnlessAthlete($request)) {
            return $denied;
        }

        $v = $request->validate([
            'stroke' => ['required', Rule::in(array_keys(AthleteRecord::STROKES))],
            'distance' => ['required', 'integer', Rule::in(AthleteRecord::DISTANCES)],
        ]);

        $rows = DB::table('tbl_athlete_records as r')
            ->join(CoachController::USERS_TABLE . ' as u', 'u.' . CoachController::USER_PK, '=', 'r.athlete_id')
            ->join('tbl_user_profile as p', 'p.user_id', '=', 'u.' . CoachController::USER_PK)
            ->where('u.' . CoachController::ROLE_COL, self::ATHLETE_ROLE_ID)
            ->where('r.stroke', $v['stroke'])
            ->where('r.distance_m', $v['distance'])
            ->whereNull('r.removed_at')
            ->orderBy('r.time_cs')
            ->orderBy('r.recorded_on')
            ->orderBy('r.record_id')
            ->get([
                'r.athlete_id',
                'r.time_cs',
                'r.recorded_on',
                'p.first_name',
                'p.middle_name',
                'p.last_name',
                'p.name_suffix'
            ]);

        $position = 0;
        $rank = 0;
        $previous = null;

        $me = (int) Auth::id();

        $entries = $rows->unique('athlete_id')->values()->map(function ($r) use (&$position, &$rank, &$previous, $me) {
            $position++;
            $cs = (int) $r->time_cs;

            if ($previous === null || $cs !== $previous) {
                $rank = $position;
            }

            $previous = $cs;

            return [
                'rank' => $rank,
                'athlete_id' => (int) $r->athlete_id,
                'is_me' => (int) $r->athlete_id === $me,
                'name' => $this->personName($r),
                'time' => AthleteRecord::formatTime($cs),
                'time_cs' => $cs,
                'date' => (string) $r->recorded_on,
            ];
        });

        return response()->json([
            'success' => true,
            'event' => AthleteRecord::eventLabel($v['stroke'], (int) $v['distance']),
            'entries' => $entries,
        ]);
    }

    /* ---------------------------------------------------------
     * GET /athlete/records/data
     * The logged-in athlete's OWN records, history and rank per event.
     * --------------------------------------------------------- */
    public function mine(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $this->isAthlete($user)) {
            return $this->fail('Only athletes can view this.', 403);
        }

        $payload = $this->athletePayload((int) $user->getKey());
        $payload['bests'] = $this->withRanks($payload['bests']);

        $first = DB::table('tbl_user_profile')
            ->where('user_id', $user->getKey())
            ->value('first_name');

        return response()->json([
            'success' => true,
            'first_name' => trim((string) $first) !== '' ? trim((string) $first) : ($user->username ?? ''),
            'strokes' => collect(AthleteRecord::STROKES)
                ->map(fn ($label, $key) => ['key' => $key, 'label' => $label])
                ->values(),
            'distances' => AthleteRecord::DISTANCES,
        ] + $payload);
    }

    /**
     * Adds "rank" and "of" to each best: the athlete's place among all
     * athletes who have a record in that event (equal times share a rank).
     */
    private function withRanks(array $bests): array
    {
        if (! $bests) {
            return [];
        }

        $events = DB::table('tbl_athlete_records as r')
            ->join(CoachController::USERS_TABLE . ' as u', 'u.' . CoachController::USER_PK, '=', 'r.athlete_id')
            ->where('u.' . CoachController::ROLE_COL, self::ATHLETE_ROLE_ID)
            ->whereNull('r.removed_at')
            ->groupBy('r.stroke', 'r.distance_m', 'r.athlete_id')
            ->selectRaw('r.stroke, r.distance_m, r.athlete_id, MIN(r.time_cs) as best')
            ->get()
            ->groupBy(fn ($row) => $row->stroke . '-' . $row->distance_m);

        foreach ($bests as $key => $best) {
            $event = $events->get($key, collect());

            $bests[$key]['rank'] = 1 + $event
                ->filter(fn ($row) => (int) $row->best < $best['time_cs'])
                ->count();

            $bests[$key]['of'] = $event->count();
        }

        return $bests;
    }

    /**
     * One athlete's current records ("bests", keyed "<stroke>-<distance>") and
     * full history, newest first. "improvement" compares each row with the
     * previous record of the same event.
     */
    private function athletePayload(int $athleteId): array
    {
        $profile = DB::table('tbl_user_profile')
            ->where('user_id', $athleteId)
            ->first([
                'first_name',
                'middle_name',
                'last_name',
                'name_suffix'
            ]);

        $records = AthleteRecord::active()
            ->where('athlete_id', $athleteId)
            ->orderBy('recorded_on')
            ->orderBy('record_id')
            ->get();

        $coaches = DB::table('tbl_user_profile')
            ->whereIn('user_id', $records->pluck('recorded_by')->unique()->all())
            ->get(['user_id', 'first_name', 'last_name'])
            ->mapWithKeys(fn ($p) => [
                (int) $p->user_id => trim($p->first_name . ' ' . $p->last_name)
            ]);

        $bests = [];
        $previous = [];
        $history = [];

        foreach ($records as $r) {
            $key = $r->stroke . '-' . $r->distance_m;

            $improvement = null;

            if (isset($previous[$key])) {
                $diff = $previous[$key] - $r->time_cs;
                $improvement = ($diff >= 0 ? '-' : '+') . AthleteRecord::formatTime(abs($diff)) . ' s';
            }

            $previous[$key] = $r->time_cs;

            if (! isset($bests[$key]) || $r->time_cs < $bests[$key]['time_cs']) {
                $bests[$key] = [
                    'time' => AthleteRecord::formatTime($r->time_cs),
                    'time_cs' => $r->time_cs,
                    'date' => (string) $r->recorded_on,
                ];
            }

            $history[] = [
                'id' => (int) $r->record_id,
                'stroke' => $r->stroke,
                'distance' => $r->distance_m,
                'event' => AthleteRecord::eventLabel($r->stroke, $r->distance_m),
                'time' => AthleteRecord::formatTime($r->time_cs),
                'date' => (string) $r->recorded_on,
                'improvement' => $improvement,
                'notes' => $r->notes ?? '',
                'recorded_by' => $coaches->get((int) $r->recorded_by, ''),
            ];
        }

        return [
            'athlete' => [
                'id' => $athleteId,
                'name' => $profile ? $this->personName($profile) : ''
            ],
            'bests' => $bests,
            'history' => array_reverse($history),
        ];
    }

    /** "Last, First M. Suffix" from a row that has the profile name columns. */
    private function personName(object $p): string
    {
        $middle = trim((string) ($p->middle_name ?? ''));
        $given = trim(($p->first_name ?? '') . ' ' . ($middle !== '' ? mb_substr($middle, 0, 1) . '.' : ''));
        $suffix = trim((string) ($p->name_suffix ?? ''));

        return trim(($p->last_name ?? '') . ', ' . $given . ($suffix !== '' ? ' ' . $suffix : ''));
    }
}