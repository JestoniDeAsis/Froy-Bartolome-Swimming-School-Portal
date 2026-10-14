@if ((int) Auth::user()->role_id === 3)
<style>
.student-page{width:100%;margin:0 auto;padding: 0px;color:#202b36}.student-page-header{margin:0 0 24px}.student-page-header h1{font-size:28px;font-weight:700;margin:0 0 5px;color:#173b63}.student-page-header p{margin:0;color:#657382;font-size:14px}.student-dashboard-grid{display:grid;grid-template-columns:minmax(0,1.65fr) minmax(280px,1fr);gap:22px}.student-dashboard-left,.student-dashboard-right{display:grid;align-content:start;gap:20px}.student-card{background:#fff;border:1px solid #dce3ea;border-radius:8px;padding:20px 22px;box-shadow:0 2px 8px rgba(26,51,77,.06)}.student-card-title{font-size:18px;font-weight:700;margin:0;color:#263d55}.student-divider{height:1px;background:#e8edf2;margin:14px 0 16px}.student-course-row{display:flex;align-items:center;gap:14px}.student-level-badge{background:#e7f2fb;color:#1765a0;border-radius:20px;padding:6px 14px;font-size:13px;font-weight:700}.student-course-row strong{display:block;font-size:15px;margin-bottom:4px}.student-table-wrap{width:100%;overflow:auto}.student-table{width:100%;border-collapse:collapse;font-size:13px;white-space:nowrap}.student-table th{font-weight:700;color:#526273;background:#f5f8fa;text-align:left;padding:11px 12px}.student-table td{padding:12px;border-bottom:1px solid #e9eef2;color:#344353}.student-status-badge{display:inline-block;border-radius:20px;padding:4px 10px;font-size:12px;font-weight:700}.student-status-badge.is-present,.student-status-badge.is-pass{background:#e7f6ed;color:#217344}.student-status-badge.is-late{background:#fff3df;color:#9a6411}.student-status-badge.is-pending{background:#fff4df;color:#946313}.student-progress-line{display:flex;justify-content:space-between;font-size:14px;color:#536273}.student-progress-line strong{color:#1768a6}.student-progress-track{height:9px;border-radius:8px;background:#e8eef3;overflow:hidden}.student-progress-fill{height:100%;width:38%;background:#1683c4;border-radius:inherit}.student-skills-list{display:grid;gap:13px}.student-skill-row{display:flex;align-items:flex-start;justify-content:space-between;gap:12px}.student-skill-name{display:flex;gap:9px;align-items:flex-start;font-size:13px;color:#3c4b5a}.student-skill-name i{color:#16834a;font-size:16px}.student-skill-row.is-pending .student-skill-name i{color:#a8b2bc}@media(max-width:900px){.student-page{padding:22px 18px}.student-dashboard-grid{grid-template-columns:1fr}}@media(max-width:560px){.student-page{padding:18px 12px}.student-card{padding:16px}.student-page-header h1{font-size:24px}}
</style>
<style>
.student-mode-switch{display:flex;align-items:center;justify-content:space-between;gap:1rem;margin:-8px 0 20px;padding:12px 14px;background:#fff;border:1px solid #dee2e6;border-radius:var(--fbss-radius, .5rem)}
.student-mode-label{margin:0;color:#495057;font-size:.875rem;font-weight:600}
.student-mode-help{margin:0;color:#6c757d;font-size:.75rem}
.student-applicant-card{min-height:300px;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center}
.student-applicant-pool{display:grid;place-items:center;width:96px;height:96px;margin-bottom:14px;border-radius:10px;background:linear-gradient(135deg,#d8f4ff,#7bd1ec 48%,#d9f5ff);color:var(--fbss-blue,#0d6efd);font-size:42px}
.student-applicant-card h2{font-size:1.1rem;font-weight:700;color:#212529;margin:0 0 8px}
.student-applicant-contact{width:min(100%,360px);margin-top:12px;padding:12px 16px;border-radius:var(--fbss-radius,.5rem);background:var(--fbss-gray-tint,#f1f2f3);text-align:left}
.student-applicant-contact p{margin:0 0 8px;color:#495057;font-size:.8rem}
.student-applicant-contact p:last-child{margin-bottom:0}
.student-applicant-contact i{width:22px;color:var(--fbss-blue,#0d6efd)}
@media(max-width:560px){.student-mode-switch{align-items:flex-start;flex-direction:column;margin-top:0}.student-mode-switch .btn-tab-group{width:100%}.student-mode-switch .btn-tab{flex:1}}
</style>
<style>
#displayContainer:has(> .student-page){padding:50px}.student-page{width:100%;max-width:none;margin:0;padding:0}@media(min-width:901px){.student-dashboard-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
</style>
@php
    $sampleAttendance = [
        ['date' => 'Jul 12, 2025', 'in' => '11:30 am', 'out' => '5:30 pm', 'status' => 'Present'],
        ['date' => 'Jul 13, 2025', 'in' => '12:30 am', 'out' => '5:30 pm', 'status' => 'Present (Late)'],
        ['date' => 'Jul 14, 2025', 'in' => '11:30 am', 'out' => '5:30 pm', 'status' => 'Present'],
        ['date' => 'Jul 15, 2025', 'in' => '11:30 am', 'out' => '5:30 pm', 'status' => 'Present'],
        ['date' => 'Jul 16, 2025', 'in' => '11:30 am', 'out' => '5:30 pm', 'status' => 'Present'],
    ];
    $sampleSkills = [
        ['name' => 'Entering and exiting the pool safely', 'status' => 'Pass'],
        ['name' => 'Submerging face underwater comfortably', 'status' => 'Pass'],
        ['name' => 'Backstroke kick', 'status' => 'Pending'],
        ['name' => 'Treading water', 'status' => 'Pending'],
    ];
@endphp

<main class="student-page student-dashboard">
    <header class="student-page-header">
        <h1>FBSS Dashboard</h1>
        <p id="studentDashboardRole">Swimmer — Applicant</p>
    </header>

    <div class="student-mode-switch" aria-label="Choose a portal preview">
        <div>
            <p class="student-mode-label">UI preview only</p>
            <p class="student-mode-help">Switching modes previews each screen; it does not change account access.</p>
        </div>
        <div class="btn-tab-group" role="group" aria-label="Preview the applicant or student screen">
            <button class="btn btn-outline-primary btn-tab active" type="button" data-student-mode-button="applicant" aria-pressed="true">Applicant</button>
            <button class="btn btn-outline-primary btn-tab" type="button" data-student-mode-button="student" aria-pressed="false">Student</button>
        </div>
    </div>

    <section class="student-card student-applicant-card" data-student-mode-panel="applicant" aria-labelledby="applicantWelcomeTitle">
        <div class="student-applicant-pool" aria-hidden="true"><i class="bi bi-water"></i></div>
        <h2 id="applicantWelcomeTitle">Looks like you haven’t joined a course yet. 🏊</h2>
        <p class="text-muted small mb-0">Visit FBSS Swim School and check out our available courses.</p>
        <p class="text-muted small mb-0">Got questions? Just message us!</p>
        <div class="student-applicant-contact" aria-label="School contact information">
            <p><i class="bi bi-facebook" aria-hidden="true"></i><a href="https://www.facebook.com/FBSSswimschool" target="_blank" rel="noopener noreferrer">FBSS Swim School</a></p>
            <p><i class="bi bi-envelope-fill" aria-hidden="true"></i><a href="mailto:fbssswimschool@gmail.com">fbssswimschool@gmail.com</a></p>
            <p><i class="bi bi-telephone-fill" aria-hidden="true"></i><a href="tel:+639662031918">(+63) 966-203-1918</a></p>
        </div>
    </section>

    <div class="student-dashboard-grid" data-student-mode-panel="student" hidden>
        <div class="student-dashboard-left">
            <section class="student-card" aria-labelledby="studentCourseTitle">
                <h2 class="student-card-title" id="studentCourseTitle">Current Course &amp; Level</h2>
                <div class="student-divider"></div>
                <div class="student-course-row">
                    <span class="student-level-badge">Beginner</span>
                    <div>
                        <strong>Learn to Swim Program</strong>
                        <div class="text-muted small">Schedule: Tue/Thu 8:00 AM</div>
                    </div>
                </div>
            </section>

            <section class="student-card" aria-labelledby="studentAttendanceTitle">
                <h2 class="student-card-title" id="studentAttendanceTitle">Recent Attendance History</h2>
                <div class="student-divider"></div>
                <div class="student-table-wrap">
                    <table class="student-table">
                        <thead>
                            <tr><th>Date</th><th>Time In</th><th>Time Out</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($sampleAttendance as $attendance)
                                <tr>
                                    <td>{{ $attendance['date'] }}</td>
                                    <td class="text-primary fw-bold">{{ $attendance['in'] }}</td>
                                    <td class="text-primary fw-bold">{{ $attendance['out'] }}</td>
                                    <td><span class="student-status-badge {{ $attendance['status'] === 'Present' ? 'is-present' : 'is-late' }}">{{ $attendance['status'] }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <div class="student-dashboard-right">
            <section class="student-card" aria-labelledby="studentProgressTitle">
                <h2 class="student-card-title" id="studentProgressTitle">Sessions Progress</h2>
                <div class="student-divider"></div>
                <div class="student-progress-line"><span>3/8 sessions completed</span><strong>38%</strong></div>
                <div class="student-progress-track mt-2" role="progressbar" aria-label="Course sessions completed" aria-valuemin="0" aria-valuemax="8" aria-valuenow="3">
                    <div class="student-progress-fill"></div>
                </div>
            </section>

            <section class="student-card" aria-labelledby="studentSkillsTitle">
                <h2 class="student-card-title" id="studentSkillsTitle">Skills Assessment Results</h2>
                <div class="student-divider"></div>
                <div class="student-skills-list">
                    @foreach ($sampleSkills as $skill)
                        <div class="student-skill-row {{ $skill['status'] === 'Pending' ? 'is-pending' : '' }}">
                            <span class="student-skill-name">
                                <i class="bi {{ $skill['status'] === 'Pass' ? 'bi-check-circle' : 'bi-circle' }}" aria-hidden="true"></i>
                                <span>{{ $skill['name'] }}</span>
                            </span>
                            <span class="student-status-badge {{ $skill['status'] === 'Pass' ? 'is-pass' : 'is-pending' }}">{{ $skill['status'] }}</span>
                        </div>
                    @endforeach
                </div>
            </section>
        </div>
    </div>
</main>
<script>
(() => {
    const buttons = document.querySelectorAll('[data-student-mode-button]');
    const panels = document.querySelectorAll('[data-student-mode-panel]');
    const roleLabel = document.getElementById('studentDashboardRole');

    buttons.forEach((button) => {
        button.addEventListener('click', () => {
            const mode = button.dataset.studentModeButton;

            buttons.forEach((option) => {
                const isActive = option === button;
                option.classList.toggle('active', isActive);
                option.setAttribute('aria-pressed', String(isActive));
            });

            panels.forEach((panel) => {
                panel.hidden = panel.dataset.studentModePanel !== mode;
            });

            roleLabel.textContent = `Swimmer — ${mode === 'applicant' ? 'Applicant' : 'Student'}`;
        });
    });
})();
</script>
@else
    DASHBOARD NI STUDENT
@endif
