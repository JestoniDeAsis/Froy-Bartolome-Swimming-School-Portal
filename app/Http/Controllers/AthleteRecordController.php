<?php

namespace App\Http\Controllers;

use App\Models\AthleteRecord;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Athlete records (personal records, "PRs").
 *
 *   Leaderboards    every coach AND every athlete can view the fastest time of
 *                   each athlete per event
 *   Manage Records  only an Active coach who is set to handle athletes can record
 *                   new records, see an athlete's history and remove a mistake
 *   My records      an athlete can view (only) their own records, history and rank
 *
 * A new record must be FASTER than the athlete's current record for that event
 * (the first one for an event is always accepted). Rows are never edited; a
 * wrong entry is removed (kept in the table with removed_at set) and the
 * previous best becomes the record again.
 */
class AthleteRecordController extends Controller
{
    private const ATHLETE_ROLE_ID = 4;

    /* ---------------------------------------------------------
     * Access
     * --------------------------------------------------------- */

    private function isCoach($user): bool
    {
        return $user && (int) ($user->{CoachController::ROLE_COL} ?? 0) === CoachController::ROLE_COACH;
    }

    /** Active coach who the admin set to also handle athletes. */
    private function canManage($user): bool
    {
        if (! $this->isCoach($user)) {
            return false;
        }

        return DB::table(CoachController::USERS_TABLE . ' as u')
            ->join(CoachController::STATUS_TABLE . ' as st', 'st.' . CoachController::STATUS_ID_COL, '=', 'u.' . CoachController::STATUS_COL)
            ->where('u.' . CoachController::USER_PK, $user->getKey())
            ->where('u.' . CoachController::ROLE_COL, CoachController::ROLE_COACH)
            ->where('u.' . CoachController::HANDLES_ATHLETES_COL, 1)
            ->where('st.' . CoachController::STATUS_NAME_COL, 'Active')
            ->exists();
    }

    private function isAthlete($user): bool
    {
        return $user && (int) ($user->{CoachController::ROLE_COL} ?? 0) === self::ATHLETE_ROLE_ID;
    }

    /** Leaderboards can be viewed by coaches and athletes. */
    private function denyUnlessViewer(Request $request): ?JsonResponse
    {
        $user = $request->user();

        return ($this->isCoach($user) || $this->isAthlete($user))
            ? null
            : response()->json(['success' => false, 'message' => 'You are not allowed to do that.'], 403);
    }

    private function denyUnlessCoach(Request $request): ?JsonResponse
    {
        return $this->isCoach($request->user())
            ? null
            : response()->json(['success' => false, 'message' => 'You are not allowed to do that.'], 403);
    }

    private function denyUnlessManager(Request $request): ?JsonResponse
    {
        return $this->canManage($request->user())
            ? null
            : response()->json(['success' => false, 'message' => 'Only coaches who handle athletes can do that.'], 403);
    }

    private function fail(string $message, int $status = 422): JsonResponse
    {
        return response()->json(['success' => false, 'message' => $message], $status);
    }

    /* ---------------------------------------------------------
     * GET /coach/athlete-records/data
     * Strokes, distances, whether this coach can manage records,
     * and (for managers) the Active athletes to pick from.
     * --------------------------------------------------------- */
    public function data(Request $request): JsonResponse
    {
        if ($denied = $this->denyUnlessCoach($request)) {
            return $denied;
        }

        $canManage = $this->canManage($request->user());

        return response()->json([
            'success' => true,
            'strokes' => collect(AthleteRecord::STROKES)
                ->map(fn ($label, $key) => ['key' => $key, 'label' => $label])
                ->values(),
            'distances' => AthleteRecord::DISTANCES,
            'can_manage' => $canManage,
            'athletes' => $canManage ? $this->activeAthletes() : [],
        ]);
    }

    /* ---------------------------------------------------------
     * GET /coach/athlete-records/leaderboard?stroke=&distance=
     * Fastest time of each athlete in one event, fastest first.
     * Equal times share a rank.
     * --------------------------------------------------------- */
    public function leaderboard(Request $request): JsonResponse
    {
        if ($denied = $this->denyUnlessViewer($request)) {
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
            ->get(['r.athlete_id', 'r.time_cs', 'r.recorded_on', 'p.first_name', 'p.middle_name', 'p.last_name', 'p.name_suffix']);

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

        $first = DB::table('tbl_user_profile')->where('user_id', $user->getKey())->value('first_name');

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

            $bests[$key]['rank'] = 1 + $event->filter(fn ($row) => (int) $row->best < $best['time_cs'])->count();
            $bests[$key]['of'] = $event->count();
        }

        return $bests;
    }

    /* ---------------------------------------------------------
     * GET /coach/athlete-records/athletes/{user}
     * One athlete's records and history (managers only).
     * --------------------------------------------------------- */
    public function athlete(Request $request, int $user): JsonResponse
    {
        if ($denied = $this->denyUnlessManager($request)) {
            return $denied;
        }

        if (! $this->isActiveAthlete($user)) {
            return $this->fail('Athlete not found.', 404);
        }

        return response()->json(['success' => true] + $this->athletePayload($user));
    }

    /* ---------------------------------------------------------
     * POST /coach/athlete-records
     * { athlete_id, stroke, distance, time, recorded_on, notes }
     * --------------------------------------------------------- */
    public function store(Request $request): JsonResponse
    {
        if ($denied = $this->denyUnlessManager($request)) {
            return $denied;
        }

        $v = $request->validate([
            'athlete_id' => ['required', 'integer'],
            'stroke' => ['required', Rule::in(array_keys(AthleteRecord::STROKES))],
            'distance' => ['required', 'integer', Rule::in(AthleteRecord::DISTANCES)],
            'time' => ['required', 'string', 'max:12'],
            // one day of slack so a server clock in another timezone never blocks "today"
            'recorded_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:' . Carbon::now()->addDay()->toDateString()],
            'notes' => ['nullable', 'string', 'max:255'],
        ], [
            'recorded_on.before_or_equal' => 'The date cannot be in the future.',
        ]);

        $cs = AthleteRecord::parseTime($v['time']);

        if ($cs === null) {
            return $this->fail('Enter the time like 31.25 or 1:05.30 (minutes:seconds.hundredths).');
        }

        $athleteId = (int) $v['athlete_id'];

        if (! $this->isActiveAthlete($athleteId)) {
            return $this->fail('Active athlete not found.', 404);
        }

        $result = DB::transaction(function () use ($v, $cs, $athleteId) {
            $best = AthleteRecord::active()
                ->where('athlete_id', $athleteId)
                ->where('stroke', $v['stroke'])
                ->where('distance_m', $v['distance'])
                ->lockForUpdate()
                ->min('time_cs');

            if ($best !== null && $cs >= (int) $best) {
                return $this->fail(
                    'A new record must be faster than the current record of ' . AthleteRecord::formatTime((int) $best)
                    . ' for ' . AthleteRecord::eventLabel($v['stroke'], (int) $v['distance']) . '.'
                );
            }

            AthleteRecord::create([
                'athlete_id' => $athleteId,
                'stroke' => $v['stroke'],
                'distance_m' => $v['distance'],
                'time_cs' => $cs,
                'recorded_on' => $v['recorded_on'],
                'recorded_by' => Auth::id(),
                'notes' => ($v['notes'] ?? '') !== '' ? $v['notes'] : null,
            ]);

            return null;
        });

        if ($result instanceof JsonResponse) {
            return $result;
        }

        return response()->json(['success' => true, 'message' => 'Record saved.'] + $this->athletePayload($athleteId), 201);
    }

    /* ---------------------------------------------------------
     * DELETE /coach/athlete-records/{record}
     * Removes a wrong entry. The row is kept with removed_at set.
     * --------------------------------------------------------- */
    public function destroy(Request $request, int $record): JsonResponse
    {
        if ($denied = $this->denyUnlessManager($request)) {
            return $denied;
        }

        $row = AthleteRecord::active()->find($record);

        if (! $row) {
            return $this->fail('Record not found.', 404);
        }

        $row->update(['removed_at' => now(), 'removed_by' => Auth::id()]);

        return response()->json(['success' => true, 'message' => 'Record removed.'] + $this->athletePayload((int) $row->athlete_id));
    }

    /* ---------------------------------------------------------
     * Helpers
     * --------------------------------------------------------- */

    private function isActiveAthlete(int $userId): bool
    {
        return DB::table(CoachController::USERS_TABLE . ' as u')
            ->join(CoachController::STATUS_TABLE . ' as st', 'st.' . CoachController::STATUS_ID_COL, '=', 'u.' . CoachController::STATUS_COL)
            ->where('u.' . CoachController::USER_PK, $userId)
            ->where('u.' . CoachController::ROLE_COL, self::ATHLETE_ROLE_ID)
            ->where('st.' . CoachController::STATUS_NAME_COL, 'Active')
            ->exists();
    }

    private function activeAthletes(): Collection
    {
        return DB::table(CoachController::USERS_TABLE . ' as u')
            ->join('tbl_user_profile as p', 'p.user_id', '=', 'u.' . CoachController::USER_PK)
            ->join(CoachController::STATUS_TABLE . ' as st', 'st.' . CoachController::STATUS_ID_COL, '=', 'u.' . CoachController::STATUS_COL)
            ->where('u.' . CoachController::ROLE_COL, self::ATHLETE_ROLE_ID)
            ->where('st.' . CoachController::STATUS_NAME_COL, 'Active')
            ->orderBy('p.last_name')
            ->orderBy('p.first_name')
            ->get(['u.' . CoachController::USER_PK . ' as id', 'p.first_name', 'p.middle_name', 'p.last_name', 'p.name_suffix'])
            ->map(fn ($a) => ['id' => (int) $a->id, 'name' => $this->personName($a)])
            ->values();
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
            ->first(['first_name', 'middle_name', 'last_name', 'name_suffix']);

        $records = AthleteRecord::active()
            ->where('athlete_id', $athleteId)
            ->orderBy('recorded_on')
            ->orderBy('record_id')
            ->get();

        $coaches = DB::table('tbl_user_profile')
            ->whereIn('user_id', $records->pluck('recorded_by')->unique()->all())
            ->get(['user_id', 'first_name', 'last_name'])
            ->mapWithKeys(fn ($p) => [(int) $p->user_id => trim($p->first_name . ' ' . $p->last_name)]);

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
            'athlete' => ['id' => $athleteId, 'name' => $profile ? $this->personName($profile) : ''],
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