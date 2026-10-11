<!--
    Tailwind CSS loaded locally for THIS VIEW ONLY.

    - important: '#skills-assessment-root' scopes every generated UTILITY
      class (w-full, p-4, etc.) to only apply inside the wrapper below.
    - corePlugins.preflight: false turns off Tailwind's global reset layer,
      which otherwise applies unscoped to raw HTML tags (img, video,
      button, etc.) everywhere on the page — including the navbar logo —
      regardless of the "important" scoping above. This is what was
      overriding the logo's height="32" attribute with height:auto and
      blowing it up. Bootstrap already provides base resets, so preflight
      isn't needed anyway.
-->
<script>
    window.tailwind = window.tailwind || {};
    tailwind.config = {
        important: '#skills-assessment-root',
        corePlugins: {
            preflight: false,
        },
    };
</script>
<script src="https://cdn.tailwindcss.com"></script>

<!--
    Tailwind's Play CDN does not reliably honor corePlugins.preflight:false
    (its reset appears to be baked into the CDN build). Rather than fight
    that further, this directly re-pins the navbar logo's size with higher
    specificity + !important, so it can't be affected no matter what
    Tailwind injects. Scoped by the logo's own src, so it can't match
    anything else on the page.
-->
<style>
    img[src*="icon_fbss"] {
        max-width: 32px !important;
        width: 32px !important;
        height: 32px !important;
    }
</style>

@php
    // Everything this page needs is queried right here, so nothing in
    // HomeController.php (or anywhere else) has to change.

    $progressOptions = ['Ongoing', 'Completed', 'Pending'];

    $selectedClass = request()->query('class');
    $selectedProgress = request()->query('progress', 'Ongoing');

    if (!in_array($selectedProgress, $progressOptions, true)) {
        $selectedProgress = 'Ongoing';
    }

    // Dropdown options: every course, regardless of enrollment.
    $classes = \DB::table('tbl_courses')
        ->orderBy('course_name')
        ->get();

    // "Progress" isn't a stored column anywhere — it's derived by comparing
    // tbl_students.session_count against the enrolled course's
    // tbl_courses.course_session_count:
    //   session_count <= 0                        => Pending
    //   0 < session_count < course_session_count   => Ongoing
    //   session_count >= course_session_count      => Completed
    //
    // Only rows that exist in tbl_students count as "enrolled swimmers".
    // A tbl_users row with role_id = 3 but no tbl_students row is a
    // registered account, not an enrolled student yet, and correctly
    // will not appear here.
    $studentsQuery = \DB::table('tbl_students as st')
        ->join('tbl_users as u', 'u.user_id', '=', 'st.user_id')
        ->join('tbl_courses as c', 'c.course_id', '=', 'st.course_id')
        ->leftJoin('tbl_user_profile as p', 'p.user_id', '=', 'u.user_id')
        ->where('u.role_id', 3)
        ->orderBy('u.username')
        ->select([
            'u.user_id',
            'u.username',
            'u.email_address',
            'st.session_count',
            'c.course_id',
            'c.course_name',
            'c.course_session_count',
            'p.first_name',
            'p.middle_name',
            'p.last_name',
        ]);

    if (!empty($selectedClass)) {
        $studentsQuery->where('c.course_id', $selectedClass);
    }

    $swimmers = [];

    foreach ($studentsQuery->get() as $student) {
        $completedSessions = max(0, (int) $student->session_count);
        $requiredSessions = max(0, (int) $student->course_session_count);

        if ($completedSessions <= 0) {
            $progress = 'Pending';
        } elseif ($requiredSessions > 0 && $completedSessions >= $requiredSessions) {
            $progress = 'Completed';
        } else {
            $progress = 'Ongoing';
        }

        if ($progress !== $selectedProgress) {
            continue;
        }

        // "Last, First Middle" to match the attendance modal design.
        // Falls back to username if this swimmer has no tbl_user_profile row.
        $lastName = trim((string) $student->last_name);
        $firstName = trim((string) $student->first_name);
        $middleName = trim((string) $student->middle_name);
        $middleInitial = $middleName !== '' ? mb_substr($middleName, 0, 1) . '.' : '';
        $firstAndMiddle = trim($firstName . ' ' . $middleInitial);

        $displayName = ($lastName !== '' && $firstAndMiddle !== '')
            ? $lastName . ', ' . $firstAndMiddle
            : $student->username;

        $swimmers[] = [
            'user_id' => $student->user_id,
            'username' => $student->username,
            'display_name' => $displayName,
            'email_address' => $student->email_address,
            'course_id' => $student->course_id,
            'course_name' => $student->course_name,
            'progress' => $progress,
        ];
    }

    // Build a checklist (categories -> skills) for every course that has at
    // least one swimmer showing on this page, so the "View Checklist" modal
    // has real data to render. No pass/fail results exist yet — that table
    // doesn't exist yet — so every skill starts unchecked; toggling a
    // checkbox in the modal is local to the browser tab only and nothing is
    // saved until the backend piece is built.
    $courseIds = collect($swimmers)->pluck('course_id')->unique()->values();

    $courseChecklists = [];

    foreach ($courseIds as $courseId) {
        $categories = \DB::table('tbl_course_categories')
            ->where('course_id', $courseId)
            ->whereNull('deleted_at')
            ->orderBy('course_category_id')
            ->get();

        $checklist = [];

        foreach ($categories as $category) {
            $skills = \DB::table('tbl_course_skills')
                ->where('course_category_id', $category->course_category_id)
                ->whereNull('deleted_at')
                ->orderBy('course_skill_id')
                ->get();

            $checklist[] = [
                'category_id' => $category->course_category_id,
                'category_name' => $category->course_category_name,
                'skills' => $skills->map(fn ($skill) => [
                    'skill_id' => $skill->course_skill_id,
                    'skill_name' => $skill->course_skill_name,
                ])->values()->all(),
            ];
        }

        $courseChecklists[$courseId] = $checklist;
    }
@endphp

<!-- Main Content Area Optimized for 1920x1008 -->
<div id="skills-assessment-root" class="w-full h-full min-h-[960px] bg-[#f4f6f8] flex flex-col antialiased text-gray-800 font-sans">

    <!-- Title Header Bar -->
    <div class="w-full bg-white border-b border-gray-200/80 px-10 py-6 shadow-xs">
        <h1 class="text-2xl font-black tracking-tight text-gray-900">SKILL ASSESSMENT</h1>
        <p class="text-xs text-gray-500 font-medium mt-1">
            Manage swimmer assessment checklists, pass/fail status, and internal notes.
        </p>
    </div>

    <!-- Main Content Container -->
    <div class="w-full max-w-[1800px] mx-auto p-10 space-y-6 flex-1">

        <!-- Filters Box -->
        <form method="GET" action="{{ url()->current() }}" class="w-full bg-white rounded-xl border border-gray-200/90 p-5 shadow-xs flex items-center space-x-6">
            
            <!-- Select Class Filter -->
            <div class="flex flex-col space-y-1.5 w-56">
                <label for="class" class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">SELECT CLASS</label>
                <div class="relative">
                    <select id="class" name="class" onchange="this.form.submit()" class="w-full appearance-none bg-white border border-gray-300 rounded-lg px-3.5 py-2 pr-9 text-xs font-medium text-gray-800 focus:outline-none focus:ring-2 focus:ring-[#00529B]/20 focus:border-[#00529B] cursor-pointer">
                        <option value="">All Courses</option>
                        @foreach ($classes ?? [] as $class)
                            @php
                                $classObj = (object)$class;
                                $classValue = $classObj->course_id ?? $classObj->id ?? $classObj->course_name ?? $classObj->name ?? '';
                                $className = $classObj->course_name ?? $classObj->name ?? 'Course';
                            @endphp
                            <option value="{{ $classValue }}" @selected(($selectedClass ?? null) == $classValue)>
                                {{ $className }}
                            </option>
                        @endforeach
                    </select>
                    <div class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none text-gray-500">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Progress Filter -->
            <div class="flex flex-col space-y-1.5 w-56">
                <label for="progress" class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">PROGRESS</label>
                <div class="relative">
                    <select id="progress" name="progress" onchange="this.form.submit()" class="w-full appearance-none bg-white border border-gray-300 rounded-lg px-3.5 py-2 pr-9 text-xs font-medium text-gray-800 focus:outline-none focus:ring-2 focus:ring-[#00529B]/20 focus:border-[#00529B] cursor-pointer">
                        @foreach ($progressOptions ?? ['Ongoing', 'Completed', 'Pending'] as $option)
                            <option value="{{ $option }}" @selected(($selectedProgress ?? 'Ongoing') == $option)>
                                {{ $option }}
                            </option>
                        @endforeach
                    </select>
                    <div class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none text-gray-500">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </div>
                </div>
            </div>

        </form>

        <!-- Swimmers List -->
        <div class="space-y-4">
            @forelse ($swimmers ?? [] as $swimmer)
                @php
                    $swimmerObj = (object)$swimmer;
                    
                    // Column mapping for tbl_users schema[cite: 9]
                    $userName = $swimmerObj->username ?? 'Unknown User';
                    $email = $swimmerObj->email_address ?? 'No email provided';
                    $userId = $swimmerObj->user_id ?? '#';
                    
                    // Generate initial from the username[cite: 9]
                    $initial = strtoupper(substr($userName, 0, 1));
                @endphp
                <div class="w-full bg-white rounded-xl border border-gray-200/90 p-4 px-6 shadow-xs flex items-center justify-between hover:border-gray-300 transition-all">
                    
                    <!-- Left: Swimmer Profile -->
                    <div class="flex items-center space-x-4">
                        <div class="w-12 h-12 rounded-full bg-[#00529B] text-white font-bold text-sm flex items-center justify-center shadow-xs flex-shrink-0">
                            {{ $initial }}
                        </div>
                        <div>
                            <h3 class="font-bold text-sm text-gray-900 leading-tight">{{ $userName }}</h3>
                            <p class="text-xs text-gray-500 font-medium mt-0.5">{{ $email }}</p>
                        </div>
                    </div>

                    <!-- Right: Action Buttons -->
                    <div class="flex items-center space-x-3">
                        <button
                            type="button"
                            data-action="open-checklist"
                            data-swimmer-name="{{ $userName }}"
                            data-checklist-target="checklist-data-{{ $userId }}"
                            class="bg-[#00529B] hover:bg-[#003d75] text-white text-xs font-semibold px-4 py-2 rounded-md transition-colors shadow-xs"
                        >
                            View Checklist
                        </button>
                        <button
                            type="button"
                            data-action="open-timeout"
                            data-student-name="{{ $swimmerObj->display_name ?? $userName }}"
                            class="bg-white hover:bg-gray-50 text-gray-800 text-xs font-semibold px-4 py-2 border border-gray-300 rounded-md transition-colors shadow-xs"
                        >
                            Time Out Check
                        </button>
                    </div>

                </div>

                {{-- Checklist data for this swimmer's enrolled course. Every
                     skill starts as passed:false — there's no saved
                     pass/fail table yet, so this is UI-only for now. --}}
                <script type="application/json" id="checklist-data-{{ $userId }}">
                    {!! json_encode(
                        collect($courseChecklists[$swimmerObj->course_id] ?? [])->map(fn ($category) => [
                            'category_name' => $category['category_name'],
                            'skills' => collect($category['skills'])->map(fn ($skill) => [
                                'skill_id' => $skill['skill_id'],
                                'skill_name' => $skill['skill_name'],
                                'passed' => false,
                            ])->values()->all(),
                        ])->values()->all(),
                        JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP
                    ) !!}
                </script>
            @empty
                <!-- Empty State when no DB records are returned -->
                <div class="bg-white rounded-xl border border-gray-200/90 p-12 text-center text-gray-500 shadow-xs">
                    <svg class="w-12 h-12 mx-auto text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                    <p class="text-sm font-semibold text-gray-700">No student records found</p>
                </div>
            @endforelse
        </div>

    </div>

    <!-- ============================================================
         LOGGING ATTENDANCE / TIME OUT CHECK MODAL (UI only — no save wired up yet)
    ============================================================= -->
    <div
        id="timeout-modal"
        data-state="hidden"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-4"
    >
        <div class="w-full max-w-md bg-white rounded-2xl shadow-xl flex flex-col overflow-hidden">

            <div class="px-6 pt-5 pb-5">
                <div class="flex items-start justify-between mb-4">
                    <h2 class="text-base font-bold text-gray-900">Logging Attendance</h2>
                    <button type="button" data-action="close-timeout" class="text-gray-400 hover:text-gray-600 -mt-1 -mr-1 p-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <p class="text-xs text-gray-500 mb-4">
                    Student name: <span id="timeout-student-name" class="font-semibold text-gray-800">—</span>
                </p>

                <label class="block text-xs font-bold text-gray-700 mb-1.5">
                    Time out <span class="text-red-500">*</span>
                </label>
                <div class="flex items-center justify-between border border-gray-300 rounded-lg pl-3.5 pr-1.5 py-1.5 focus-within:ring-2 focus-within:ring-[#00529B]/20 focus-within:border-[#00529B]">
                    <div class="flex items-center flex-1">
                        <input
                            id="timeout-time-input"
                            type="text"
                            inputmode="numeric"
                            placeholder="0:00"
                            maxlength="5"
                            class="text-sm font-medium text-gray-800 bg-transparent focus:outline-none w-16"
                        >
                        <div class="relative ml-2">
                            <select
                                id="timeout-period-select"
                                class="appearance-none text-sm font-medium text-gray-800 bg-gray-50 border border-gray-200 rounded-md pl-2.5 pr-7 py-1 focus:outline-none focus:ring-2 focus:ring-[#00529B]/20 focus:border-[#00529B] cursor-pointer"
                            >
                                <option value="AM">AM</option>
                                <option value="PM">PM</option>
                            </select>
                            <div class="absolute right-2 top-1/2 -translate-y-1/2 pointer-events-none text-gray-500">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                    <button
                        type="button"
                        data-action="use-current-time"
                        class="text-[11px] font-bold text-[#00529B] hover:text-[#003d75] uppercase tracking-wide flex-shrink-0 ml-3 px-2"
                    >
                        Current
                    </button>
                </div>
                <p class="text-[11px] text-gray-400 mt-1.5">Format: H:MM</p>
            </div>

            <div class="px-6 py-4 border-t border-gray-200/90 flex items-center justify-end space-x-2">
                <button type="button" data-action="close-timeout" class="text-xs font-semibold px-4 py-2 rounded-md border border-gray-300 text-gray-700 hover:bg-gray-50 transition-colors">
                    Cancel
                </button>
                <button type="button" data-action="confirm-timeout" class="text-xs font-semibold px-5 py-2 rounded-md bg-[#00529B] hover:bg-[#003d75] text-white transition-colors">
                    Confirm
                </button>
            </div>

        </div>
    </div>

    <!-- ============================================================
         CORE SKILLS CHECKLIST MODAL (UI only — no save wired up yet)
    ============================================================= -->
    <div
        id="checklist-modal"
        data-state="hidden"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-4"
    >
        <div class="w-full max-w-xl bg-white rounded-2xl shadow-xl flex flex-col max-h-[85vh] overflow-hidden">

            <!-- Modal Header -->
            <div class="px-6 pt-5 pb-4 border-b border-gray-200/90 flex-shrink-0">
                <div class="flex items-start justify-between">
                    <div>
                        <h2 id="checklist-swimmer-name" class="text-base font-bold text-gray-900">Swimmer</h2>
                        <p class="text-xs font-semibold text-gray-500 mt-0.5">Core Skills Checklist</p>
                    </div>
                    <button type="button" data-action="close-checklist" class="text-gray-400 hover:text-gray-600 -mt-1 -mr-1 p-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <div class="mt-4 flex items-center justify-between text-xs font-semibold">
                    <span id="checklist-progress-fraction" class="text-gray-700">0 / 0 passed</span>
                    <span id="checklist-progress-percent" class="text-gray-500">0%</span>
                </div>
                <div class="mt-1.5 w-full h-1.5 bg-gray-100 rounded-full overflow-hidden">
                    <div id="checklist-progress-bar" class="h-full bg-[#00529B] transition-all" style="width: 0%"></div>
                </div>
            </div>

            <!-- Modal Body: categories + skills, filled in by JS -->
            <div id="checklist-body" class="px-6 py-4 space-y-5 overflow-y-auto flex-1">
                <!-- Populated by JS -->
            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-4 border-t border-gray-200/90 flex items-center justify-between flex-shrink-0">
                <label class="flex items-center space-x-2 text-xs font-semibold text-gray-700 cursor-pointer">
                    <input id="checklist-ready-to-graduate" type="checkbox" class="rounded border-gray-300 text-[#00529B] focus:ring-[#00529B]/30">
                    <span>Mark as Ready to Graduate</span>
                </label>
                <div class="flex items-center space-x-2">
                    <button type="button" data-action="close-checklist" class="text-xs font-semibold px-4 py-2 rounded-md border border-gray-300 text-gray-700 hover:bg-gray-50 transition-colors">
                        Cancel
                    </button>
                    <button type="button" data-action="save-checklist" class="text-xs font-semibold px-4 py-2 rounded-md bg-[#00529B] hover:bg-[#003d75] text-white transition-colors">
                        Save
                    </button>
                </div>
            </div>

        </div>
    </div>

</div>

<script>
(function () {
    // Guard so this only wires up once even if the view is ever re-rendered.
    if (window.__skillsAssessmentChecklistInitialized) {
        return;
    }
    window.__skillsAssessmentChecklistInitialized = true;

    var modal = document.getElementById('checklist-modal');
    var currentChecklist = null; // in-memory only — nothing persists yet

    function openChecklist(swimmerName, dataScriptId) {
        var dataEl = document.getElementById(dataScriptId);
        var categories = [];

        if (dataEl) {
            try {
                categories = JSON.parse(dataEl.textContent) || [];
            } catch (error) {
                console.error('Could not parse checklist data for ' + dataScriptId, error);
            }
        }

        currentChecklist = categories;

        document.getElementById('checklist-swimmer-name').textContent = swimmerName || 'Swimmer';
        document.getElementById('checklist-ready-to-graduate').checked = false;

        renderChecklistBody();
        updateProgress();

        modal.dataset.state = 'visible';
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeChecklist() {
        modal.dataset.state = 'hidden';
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        currentChecklist = null;
    }

    function renderChecklistBody() {
        var body = document.getElementById('checklist-body');
        body.innerHTML = '';

        if (!currentChecklist || currentChecklist.length === 0) {
            var empty = document.createElement('p');
            empty.className = 'text-xs text-gray-500 text-center py-6';
            empty.textContent = 'No skill categories have been set up for this course yet.';
            body.appendChild(empty);
            return;
        }

        currentChecklist.forEach(function (category, categoryIndex) {
            var section = document.createElement('div');

            var header = document.createElement('div');
            header.className = 'flex items-center justify-between mb-2';

            var title = document.createElement('h3');
            title.className = 'text-xs font-bold text-gray-800 uppercase tracking-wide';
            title.textContent = category.category_name;

            var countBadge = document.createElement('span');
            countBadge.className = 'text-[11px] font-semibold text-gray-400';
            countBadge.id = 'category-count-' + categoryIndex;

            header.appendChild(title);
            header.appendChild(countBadge);
            section.appendChild(header);

            var list = document.createElement('div');
            list.className = 'space-y-1.5';

            category.skills.forEach(function (skill, skillIndex) {
                list.appendChild(renderSkillRow(categoryIndex, skillIndex, skill));
            });

            section.appendChild(list);
            body.appendChild(section);
        });

        currentChecklist.forEach(function (_, categoryIndex) {
            updateCategoryCount(categoryIndex);
        });
    }

    function renderSkillRow(categoryIndex, skillIndex, skill) {
        var row = document.createElement('label');
        row.className = 'flex items-center justify-between px-3 py-2 rounded-lg bg-gray-50 hover:bg-gray-100 cursor-pointer transition-colors';

        var left = document.createElement('div');
        left.className = 'flex items-center space-x-2.5';

        var checkbox = document.createElement('input');
        checkbox.type = 'checkbox';
        checkbox.checked = !!skill.passed;
        checkbox.className = 'rounded border-gray-300 text-[#00529B] focus:ring-[#00529B]/30';
        checkbox.dataset.categoryIndex = categoryIndex;
        checkbox.dataset.skillIndex = skillIndex;

        var name = document.createElement('span');
        name.className = 'text-xs font-medium text-gray-700';
        name.textContent = skill.skill_name;

        left.appendChild(checkbox);
        left.appendChild(name);

        var status = document.createElement('span');
        status.className = 'checklist-status-badge text-[11px] font-bold px-2 py-0.5 rounded-full';

        row.appendChild(left);
        row.appendChild(status);

        setStatusBadge(status, checkbox.checked);

        checkbox.addEventListener('change', function () {
            currentChecklist[categoryIndex].skills[skillIndex].passed = checkbox.checked;
            setStatusBadge(status, checkbox.checked);
            updateCategoryCount(categoryIndex);
            updateProgress();
        });

        return row;
    }

    function setStatusBadge(badgeEl, passed) {
        if (passed) {
            badgeEl.textContent = 'Passed';
            badgeEl.className = 'checklist-status-badge text-[11px] font-bold px-2 py-0.5 rounded-full bg-green-50 text-green-600';
        } else {
            badgeEl.textContent = 'Not Yet';
            badgeEl.className = 'checklist-status-badge text-[11px] font-bold px-2 py-0.5 rounded-full bg-gray-100 text-gray-400';
        }
    }

    function updateCategoryCount(categoryIndex) {
        var category = currentChecklist[categoryIndex];
        var passed = category.skills.filter(function (s) { return s.passed; }).length;
        var badge = document.getElementById('category-count-' + categoryIndex);
        if (badge) {
            badge.textContent = passed + ' / ' + category.skills.length + ' passed';
        }
    }

    function updateProgress() {
        var totalSkills = 0;
        var totalPassed = 0;

        (currentChecklist || []).forEach(function (category) {
            totalSkills += category.skills.length;
            totalPassed += category.skills.filter(function (s) { return s.passed; }).length;
        });

        var percent = totalSkills > 0 ? Math.round((totalPassed / totalSkills) * 100) : 0;

        document.getElementById('checklist-progress-fraction').textContent = totalPassed + ' / ' + totalSkills + ' passed';
        document.getElementById('checklist-progress-percent').textContent = percent + '%';
        document.getElementById('checklist-progress-bar').style.width = percent + '%';
    }

    document.addEventListener('click', function (event) {
        var openTrigger = event.target.closest('[data-action="open-checklist"]');
        if (openTrigger) {
            openChecklist(openTrigger.dataset.swimmerName, openTrigger.dataset.checklistTarget);
            return;
        }

        if (event.target.closest('[data-action="close-checklist"]')) {
            closeChecklist();
            return;
        }

        // Clicking the dark backdrop (but not the modal panel itself) closes it too.
        if (event.target === modal) {
            closeChecklist();
            return;
        }

        if (event.target.closest('[data-action="save-checklist"]')) {
            // No backend wired up yet — this is UI only for now.
            if (typeof showToast === 'function') {
                showToast('This is a UI preview only — saving isn\'t connected yet.', 'info');
            }
            closeChecklist();
            return;
        }

        var timeoutOpenTrigger = event.target.closest('[data-action="open-timeout"]');
        if (timeoutOpenTrigger) {
            openTimeout(timeoutOpenTrigger.dataset.studentName);
            return;
        }

        if (event.target.closest('[data-action="close-timeout"]')) {
            closeTimeout();
            return;
        }

        if (event.target === timeoutModal) {
            closeTimeout();
            return;
        }

        if (event.target.closest('[data-action="use-current-time"]')) {
            setTimeoutFieldToNow();
            return;
        }

        if (event.target.closest('[data-action="confirm-timeout"]')) {
            // No backend wired up yet — this is UI only for now.
            if (typeof showToast === 'function') {
                showToast('This is a UI preview only — logging attendance isn\'t connected yet.', 'info');
            }
            closeTimeout();
        }
    });

    /* =========================================
       LOGGING ATTENDANCE / TIME OUT CHECK MODAL
    ========================================= */

    var timeoutModal = document.getElementById('timeout-modal');

    function getCurrentTimeParts() {
        var now = new Date();
        var hours24 = now.getHours();
        var period = hours24 >= 12 ? 'PM' : 'AM';
        var hours12 = hours24 % 12;
        if (hours12 === 0) hours12 = 12;
        var minutes = String(now.getMinutes()).padStart(2, '0');

        return { time: hours12 + ':' + minutes, period: period };
    }

    function setTimeoutFieldToNow() {
        var parts = getCurrentTimeParts();
        document.getElementById('timeout-time-input').value = parts.time;
        document.getElementById('timeout-period-select').value = parts.period;
    }

    function openTimeout(studentName) {
        document.getElementById('timeout-student-name').textContent = studentName || '—';
        setTimeoutFieldToNow();

        timeoutModal.dataset.state = 'visible';
        timeoutModal.classList.remove('hidden');
        timeoutModal.classList.add('flex');
    }

    function closeTimeout() {
        timeoutModal.dataset.state = 'hidden';
        timeoutModal.classList.add('hidden');
        timeoutModal.classList.remove('flex');
    }

    // Lets someone type "911" and get "9:11" automatically, and blocks
    // anything that isn't a digit or colon.
    document.getElementById('timeout-time-input').addEventListener('input', function (event) {
        var digitsOnly = event.target.value.replace(/[^0-9]/g, '').slice(0, 4);

        if (digitsOnly.length <= 2) {
            event.target.value = digitsOnly;
        } else {
            event.target.value = digitsOnly.slice(0, digitsOnly.length - 2) + ':' + digitsOnly.slice(-2);
        }
    });
})();
</script>