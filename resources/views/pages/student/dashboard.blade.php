@if ((int) Auth::user()->role_id === 3)
<style>
.student-page{width:100%;margin:0 auto;padding: 0px;color:#202b36}.student-page-header{margin:0 0 24px}.student-page-header h1{font-size:28px;font-weight:700;margin:0 0 5px;color:#173b63}.student-page-header p{margin:0;color:#657382;font-size:14px}.student-dashboard-grid{display:grid;grid-template-columns:minmax(0,1.65fr) minmax(280px,1fr);gap:22px}.student-dashboard-left,.student-dashboard-right{display:grid;align-content:start;gap:20px}.student-card{background:#fff;border:1px solid #dce3ea;border-radius:8px;padding:20px 22px;box-shadow:0 2px 8px rgba(26,51,77,.06)}.student-card-title{font-size:18px;font-weight:700;margin:0;color:#263d55}.student-divider{height:1px;background:#e8edf2;margin:14px 0 16px}.student-course-row{display:flex;align-items:center;gap:14px}.student-level-badge{background:#e7f2fb;color:#1765a0;border-radius:20px;padding:6px 14px;font-size:13px;font-weight:700}.student-course-row strong{display:block;font-size:15px;margin-bottom:4px}.student-table-wrap{width:100%;overflow:auto}.student-table{width:100%;border-collapse:collapse;font-size:13px;white-space:nowrap}.student-table th{font-weight:700;color:#526273;background:#f5f8fa;text-align:left;padding:11px 12px}.student-table td{padding:12px;border-bottom:1px solid #e9eef2;color:#344353}.student-status-badge{display:inline-block;border-radius:20px;padding:4px 10px;font-size:12px;font-weight:700}.student-status-badge.is-present,.student-status-badge.is-pass{background:#e7f6ed;color:#217344}.student-status-badge.is-late{background:#fff3df;color:#9a6411}.student-status-badge.is-pending{background:#fff4df;color:#946313}.student-progress-line{display:flex;justify-content:space-between;font-size:14px;color:#536273}.student-progress-line strong{color:#1768a6}.student-progress-track{height:9px;border-radius:8px;background:#e8eef3;overflow:hidden}.student-progress-fill{height:100%;width:38%;background:#1683c4;border-radius:inherit}.student-skills-list{display:grid;gap:13px}.student-skill-row{display:flex;align-items:flex-start;justify-content:space-between;gap:12px}.student-skill-name{display:flex;gap:9px;align-items:flex-start;font-size:13px;color:#3c4b5a}.student-skill-name i{color:#16834a;font-size:16px}.student-skill-row.is-pending .student-skill-name i{color:#a8b2bc}@media(max-width:900px){.student-page{padding:22px 18px}.student-dashboard-grid{grid-template-columns:1fr}}@media(max-width:560px){.student-page{padding:18px 12px}.student-card{padding:16px}.student-page-header h1{font-size:24px}}
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
        <p>Swimmer — Student</p>
    </header>

    <div class="student-dashboard-grid">
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
@else
    DASHBOARD NI STUDENT
@endif
