@extends('layouts.toast')

@php
    // Everything comes from StudentDashboardController::pageData(). The page is
    // rendered by name from role_menus.php (no route), so it is called here.
    $dashboard = app(\App\Http\Controllers\StudentDashboardController::class)->pageData(auth()->user());

    $state     = $dashboard['state'];       // none | past | current
    $firstName = $dashboard['firstName'];
    $contact   = $dashboard['contact'];
    $current   = $dashboard['current'];
    $past      = $dashboard['past'];

    $peso = fn ($amount) => '₱' . number_format((float) $amount, 2);

    // Pill colour for an attendance status (Present / Late / Absent).
    $attendancePill = function (string $state): string {
        if (stripos($state, 'absent') !== false) return 'is-failed';
        if (stripos($state, 'late') !== false) return 'is-pending';
        return 'is-pass';
    };
@endphp

<style>
/* Page-specific layout only. Typography, buttons and the base look come from
   default.css / our_theme.css. */

.studentDash {
    width: 100%;
    padding: 1.25rem;
    box-sizing: border-box;
}

.studentDashHeader { margin-bottom: 1rem; }

.studentDashGrid {
    display: grid;
    grid-template-columns: minmax(0, 7fr) minmax(0, 5fr);
    gap: 1rem;
    align-items: start;
}

.studentDashCol { display: grid; gap: 1rem; align-content: start; }

.studentDashCard {
    padding: 1.1rem 1.25rem;
    background: #fff;
    border: 1px solid #e7ebef;
    border-radius: 12px;
}

.studentDashCardTitle {
    margin: 0 0 .85rem;
    color: #667085;
    font-size: .75rem;
    font-weight: 700;
    letter-spacing: .04em;
    text-transform: uppercase;
}

/* ---- Current course ---- */

.studentDashCourseTop {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: .75rem;
    flex-wrap: wrap;
}

.studentDashCourseName { margin: 0; color: #1f2937; font-size: 1.35rem; font-weight: 700; }

.studentDashMeta {
    display: flex;
    flex-wrap: wrap;
    gap: .35rem 1.1rem;
    margin-top: .6rem;
    color: #667085;
    font-size: .85rem;
}

.studentDashMeta i { margin-right: .3rem; }

/* ---- Sessions ---- */

.studentDashBig { margin: 0; color: #1f2937; font-size: 1.6rem; font-weight: 700; line-height: 1.1; }
.studentDashBig span { color: #667085; font-size: .9rem; font-weight: 500; }

.studentDashProgressTop {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: .5rem;
    margin-bottom: .5rem;
}

.studentDashPercent { color: var(--fbss-blue, #0d6efd); font-size: 1rem; font-weight: 700; }

.studentDash .progress { height: 8px; background: #e7ebf0; border-radius: 99px; }
.studentDash .progress-bar { background: var(--fbss-blue, #0d6efd); border-radius: 99px; }

.studentDashNote { margin: .6rem 0 0; color: #667085; font-size: .8125rem; }

/* ---- Pills ---- */

.studentDashPill {
    display: inline-block;
    padding: .2rem .65rem;
    border-radius: 999px;
    font-size: .75rem;
    font-weight: 600;
    white-space: nowrap;
}

.studentDashPill.is-pass    { background: var(--fbss-success-tint, #e6f7ec); color: var(--fbss-success, #1a7f3c); }
.studentDashPill.is-failed  { background: var(--fbss-danger-tint, #fdebec);  color: var(--fbss-danger, #c22b3a); }
.studentDashPill.is-pending { background: #fff4e0; color: #b26a00; }

/* ---- Skills ---- */

.studentDashSkillsHead {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: .5rem;
    margin-bottom: .5rem;
}

.studentDashSkillsScroll { max-height: 440px; margin-top: 1rem; padding-right: .25rem; overflow-y: auto; }

.studentDashCategory + .studentDashCategory { margin-top: 1.1rem; }

.studentDashCategoryHead {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: .45rem;
}

.studentDashCategoryTitle {
    margin: 0;
    color: #344054;
    font-size: .8125rem;
    font-weight: 700;
    letter-spacing: .03em;
    text-transform: uppercase;
}

.studentDashCategoryCount { color: #667085; font-size: .75rem; font-weight: 600; }

.studentDashSkillList { display: grid; gap: .4rem; }

.studentDashSkill {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .75rem;
    padding: .55rem .75rem;
    background: #f8fafb;
    border-radius: 8px;
    color: #344054;
    font-size: .875rem;
    font-weight: 500;
}

.studentDashSkill i { margin-right: .5rem; }
.studentDashSkill .bi-check-circle-fill { color: var(--fbss-success, #1a7f3c); }
.studentDashSkill .bi-x-circle-fill     { color: var(--fbss-danger, #c22b3a); }
.studentDashSkill .bi-circle            { color: #adb5bd; }

/* ---- Enrollment details ---- */

.studentDashDetails { margin: 0; display: grid; gap: .7rem; }

.studentDashDetails > div {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: 1rem;
    padding-bottom: .7rem;
    border-bottom: 1px solid #f1f3f5;
}

.studentDashDetails > div:last-child { padding-bottom: 0; border-bottom: 0; }
.studentDashDetails dt { color: #667085; font-size: .85rem; font-weight: 500; }
.studentDashDetails dd { margin: 0; color: #1f2937; font-size: .9rem; font-weight: 600; text-align: right; }
.studentDashDetails dd.isOwing { color: var(--fbss-danger, #c22b3a); }
.studentDashDetails dd.isPaid  { color: var(--fbss-success, #1a7f3c); }

/* ---- Recent attendance (brief) ---- */

.studentDashAttendanceList { display: grid; gap: .5rem; }

.studentDashAttendanceRow {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .75rem;
    padding: .55rem .75rem;
    background: #f8fafb;
    border-radius: 8px;
}

.studentDashAttendanceDate { color: #1f2937; font-size: .85rem; font-weight: 600; }
.studentDashAttendanceTime { color: #667085; font-size: .75rem; }

/* ---- Empty / past states ---- */

.studentDashMessage {
    max-width: 640px;
    margin: 1.5rem auto 0;
    padding: 2.25rem 1.5rem;
    text-align: center;
}

.studentDashMessageIcon {
    display: grid;
    place-items: center;
    width: 4.5rem;
    height: 4.5rem;
    margin: 0 auto 1rem;
    border-radius: 50%;
    background: #e7f1ff;
    color: var(--fbss-blue, #0d6efd);
    font-size: 2rem;
}

.studentDashMessageIcon.isSuccess { background: var(--fbss-success-tint, #e6f7ec); color: var(--fbss-success, #1a7f3c); }

.studentDashMessage h3 { margin: 0 0 .4rem; color: #1f2937; font-size: 1.2rem; font-weight: 700; }
.studentDashMessage p { margin: 0 0 .25rem; color: #667085; font-size: .9rem; }

.studentDashContact {
    display: grid;
    gap: .55rem;
    width: min(100%, 360px);
    margin: 1.25rem auto 0;
    padding: .9rem 1.1rem;
    background: #f8fafb;
    border-radius: 10px;
    text-align: left;
}

.studentDashContact p { margin: 0; color: #495057; font-size: .85rem; }
.studentDashContact i { width: 1.4rem; color: var(--fbss-blue, #0d6efd); }

.studentDashPastList { display: grid; gap: .75rem; max-width: 640px; margin: 1rem auto 0; }

.studentDashPastCard { display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap; }

.studentDashPastName { margin: 0; color: #1f2937; font-size: 1rem; font-weight: 700; }

.studentDashPastPills { display: flex; flex-wrap: wrap; gap: .4rem; }

@media (max-width: 991.98px) {
    .studentDashGrid { grid-template-columns: minmax(0, 1fr); }
}

@media (max-width: 575.98px) {
    .studentDash { padding: 1rem; }
}
</style>

<section class="studentDash" id="studentDash" aria-labelledby="studentDashTitle">

    <header class="studentDashHeader">
        <h2 class="mb-1" id="studentDashTitle">Dashboard</h2>
        <p class="text-muted mb-0">Welcome, {{ $firstName }}.</p>
    </header>

    @if ($state === 'none')

        {{-- STATE 1: no current or past enrollment --}}
        <div class="studentDashCard studentDashMessage" id="studentDashNone">
            <div class="studentDashMessageIcon"><i class="bi bi-water" aria-hidden="true"></i></div>
            <h3>You are not yet registered to any courses.</h3>
            <p>Visit FBSS Swim School and check out our available courses.</p>
            <p>Got questions? Just message us!</p>

            <div class="studentDashContact" aria-label="School contact information">
                <p><i class="bi bi-facebook" aria-hidden="true"></i><a href="{{ $contact['facebookUrl'] }}" target="_blank" rel="noopener noreferrer">{{ $contact['facebookLabel'] }}</a></p>
                <p><i class="bi bi-envelope-fill" aria-hidden="true"></i><a href="mailto:{{ $contact['email'] }}">{{ $contact['email'] }}</a></p>
                <p><i class="bi bi-telephone-fill" aria-hidden="true"></i><a href="tel:{{ $contact['phoneLink'] }}">{{ $contact['phoneLabel'] }}</a></p>
            </div>
        </div>

    @elseif ($state === 'past')

        {{-- STATE 2: enrolled before, nothing current --}}
        @php $latest = $past[0]; @endphp

        <div class="studentDashCard studentDashMessage" id="studentDashPast">
            <div class="studentDashMessageIcon isSuccess"><i class="bi bi-mortarboard-fill" aria-hidden="true"></i></div>
            <h3>Congratulations on finishing your course!</h3>
            <p>
                You completed <strong>{{ $latest['courseName'] }}</strong>@if ($latest['graduatedAt']) on {{ $latest['graduatedAt'] }}@endif.
                You have no active enrollment right now.
            </p>
            <p>To enroll again, please contact FBSS Swim School.</p>

            <div class="studentDashContact" aria-label="School contact information">
                <p><i class="bi bi-facebook" aria-hidden="true"></i><a href="{{ $contact['facebookUrl'] }}" target="_blank" rel="noopener noreferrer">{{ $contact['facebookLabel'] }}</a></p>
                <p><i class="bi bi-envelope-fill" aria-hidden="true"></i><a href="mailto:{{ $contact['email'] }}">{{ $contact['email'] }}</a></p>
                <p><i class="bi bi-telephone-fill" aria-hidden="true"></i><a href="tel:{{ $contact['phoneLink'] }}">{{ $contact['phoneLabel'] }}</a></p>
            </div>
        </div>

        <div class="studentDashPastList" id="studentDashPastList">
            @foreach ($past as $item)
                <article class="studentDashCard studentDashPastCard">
                    <div>
                        <h4 class="studentDashPastName">{{ $item['courseName'] }}</h4>
                        <div class="studentDashMeta">
                            @if ($item['enrolledAt'])<span><i class="bi bi-calendar-event"></i>Enrolled {{ $item['enrolledAt'] }}</span>@endif
                            <span><i class="bi bi-mortarboard"></i>Graduated {{ $item['graduatedAt'] }}</span>
                        </div>
                    </div>
                    <div class="studentDashPastPills">
                        <span class="studentDashPill is-pass">{{ $item['done'] }}{{ $item['total'] > 0 ? ' / ' . $item['total'] : '' }} sessions</span>
                        @if ($item['skillsTotal'] > 0)
                            <span class="studentDashPill is-pass">{{ $item['skillsPassed'] }} / {{ $item['skillsTotal'] }} skills passed</span>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>

    @else

        {{-- STATE 3: currently enrolled and not graduated --}}
        @php
            $percent = $current['total'] > 0 ? min(100, (int) round(($current['done'] / $current['total']) * 100)) : 0;
            $left = max($current['total'] - $current['done'], 0);
            $skillsPercent = $current['skillsTotal'] > 0 ? (int) round(($current['skillsPassed'] / $current['skillsTotal']) * 100) : 0;
        @endphp

        <div class="studentDashGrid" id="studentDashCurrent">

            <div class="studentDashCol">

                {{-- Current course --}}
                <article class="studentDashCard" id="studentDashCourse">
                    <h3 class="studentDashCardTitle">Current Course</h3>
                    <div class="studentDashCourseTop">
                        <h4 class="studentDashCourseName">{{ $current['courseName'] }}</h4>
                        @if ($current['eligible'])
                            <span class="studentDashPill is-pass"><i class="bi bi-patch-check-fill me-1"></i>Eligible to graduate</span>
                        @endif
                    </div>
                    <div class="studentDashMeta">
                        @if ($current['schedule'])<span><i class="bi bi-clock"></i>{{ $current['schedule'] }}</span>@endif
                        @if ($current['enrolledAt'])<span><i class="bi bi-calendar-event"></i>Enrolled {{ $current['enrolledAt'] }}</span>@endif
                    </div>
                </article>

                {{-- Skills assessment --}}
                <article class="studentDashCard" id="studentDashSkills">
                    <h3 class="studentDashCardTitle">Skills Assessment Results</h3>

                    @if ($current['skillsTotal'] === 0)
                        <p class="studentDashNote mt-0">No skills have been set up for this course yet.</p>
                    @else
                        <div class="studentDashSkillsHead">
                            <p class="studentDashBig">{{ $current['skillsPassed'] }}<span> / {{ $current['skillsTotal'] }} skills passed</span></p>
                            <span class="studentDashPercent">{{ $skillsPercent }}%</span>
                        </div>
                        <div class="progress" role="progressbar" aria-label="Skills passed" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $skillsPercent }}">
                            <div class="progress-bar" style="width: {{ $skillsPercent }}%"></div>
                        </div>

                        <div class="studentDashSkillsScroll">
                            @foreach ($current['categories'] as $category)
                                <section class="studentDashCategory">
                                    <div class="studentDashCategoryHead">
                                        <h5 class="studentDashCategoryTitle">{{ $category['name'] }}</h5>
                                        <span class="studentDashCategoryCount">{{ $category['passed'] }} / {{ $category['total'] }} passed</span>
                                    </div>
                                    <div class="studentDashSkillList">
                                        @foreach ($category['skills'] as $skill)
                                            @php
                                                $icon = $skill['result'] === 'pass' ? 'bi-check-circle-fill' : ($skill['result'] === 'failed' ? 'bi-x-circle-fill' : 'bi-circle');
                                                $pillClass = $skill['result'] === 'pass' ? 'is-pass' : ($skill['result'] === 'failed' ? 'is-failed' : 'is-pending');
                                                $pillText = $skill['result'] === 'pass' ? 'Pass' : ($skill['result'] === 'failed' ? 'Failed' : 'Pending');
                                            @endphp
                                            <div class="studentDashSkill">
                                                <span><i class="bi {{ $icon }}" aria-hidden="true"></i>{{ $skill['name'] }}</span>
                                                <span class="studentDashPill {{ $pillClass }}">{{ $pillText }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </section>
                            @endforeach
                        </div>
                    @endif
                </article>

            </div>

            <div class="studentDashCol">

                {{-- Sessions progress --}}
                <article class="studentDashCard" id="studentDashSessions">
                    <h3 class="studentDashCardTitle">Sessions Progress</h3>
                    <div class="studentDashProgressTop">
                        <p class="studentDashBig">{{ $current['done'] }}<span> {{ $current['total'] > 0 ? '/ ' . $current['total'] . ' sessions' : 'sessions attended' }}</span></p>
                        @if ($current['total'] > 0)<span class="studentDashPercent">{{ $percent }}%</span>@endif
                    </div>
                    <div class="progress" role="progressbar" aria-label="Sessions completed" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $percent }}">
                        <div class="progress-bar" style="width: {{ $percent }}%"></div>
                    </div>
                    <p class="studentDashNote">
                        @if ($current['total'] > 0)
                            {{ $left === 0 ? 'All sessions completed.' : $left . ' session' . ($left === 1 ? '' : 's') . ' left.' }}
                        @endif
                        {{ $current['lastSession'] ? 'Last session: ' . $current['lastSession'] . '.' : 'No sessions logged yet.' }}
                    </p>
                </article>

                {{-- Enrollment details --}}
                <article class="studentDashCard" id="studentDashEnrollment">
                    <h3 class="studentDashCardTitle">Enrollment Details</h3>
                    <dl class="studentDashDetails">
                        <div><dt>Course</dt><dd>{{ $current['courseName'] }}</dd></div>
                        <div><dt>Schedule</dt><dd>{{ $current['schedule'] ?: 'Not set' }}</dd></div>
                        <div><dt>Enrolled on</dt><dd>{{ $current['enrolledAt'] ?? '-' }}</dd></div>
                        <div><dt>Course price</dt><dd>{{ $peso($current['price']) }}</dd></div>
                        <div><dt>Total paid</dt><dd>{{ $peso($current['paid']) }}</dd></div>
                        <div>
                            <dt>Balance</dt>
                            <dd class="{{ $current['balance'] > 0 ? 'isOwing' : 'isPaid' }}">
                                {{ $current['balance'] > 0 ? $peso($current['balance']) : 'Fully paid' }}
                            </dd>
                        </div>
                    </dl>
                </article>

                {{-- Recent attendance (brief; the full history is on the Attendance page) --}}
                <article class="studentDashCard" id="studentDashAttendance">
                    <h3 class="studentDashCardTitle">Recent Attendance</h3>

                    @if (empty($current['attendance']))
                        <p class="studentDashNote mt-0">No attendance logged yet.</p>
                    @else
                        <div class="studentDashAttendanceList">
                            @foreach ($current['attendance'] as $row)
                                <div class="studentDashAttendanceRow">
                                    <div>
                                        <div class="studentDashAttendanceDate">{{ $row['date'] }}</div>
                                        <div class="studentDashAttendanceTime">{{ $row['in'] ?? '-' }} &ndash; {{ $row['out'] ?? '-' }}</div>
                                    </div>
                                    <span class="studentDashPill {{ $attendancePill($row['state']) }}">{{ $row['state'] }}</span>
                                </div>
                            @endforeach
                        </div>
                        <p class="studentDashNote">See the Attendance page for your full history.</p>
                    @endif
                </article>

            </div>

        </div>

    @endif

</section>