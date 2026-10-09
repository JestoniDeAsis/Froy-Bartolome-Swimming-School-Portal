@if ((int) Auth::user()->role_id === 3)
<style>
.student-page{width:100%;max-width:1320px;margin:0 auto;padding:28px 32px 40px;color:#202b36}.student-page-header{margin:0 0 24px}.student-page-header h1{font-size:28px;font-weight:700;margin:0 0 5px;color:#173b63}.student-page-header p{margin:0;color:#657382;font-size:14px}.student-card{background:#fff;border:1px solid #dce3ea;border-radius:8px;padding:20px 22px;box-shadow:0 2px 8px rgba(26,51,77,.06)}.student-attendance-toolbar{display:flex;align-items:end;justify-content:space-between;gap:16px;margin-bottom:20px}.student-attendance-toolbar label{display:grid;gap:7px;color:#526273;font-size:13px;font-weight:600}.student-attendance-toolbar select{min-width:220px;border:1px solid #cbd5df;border-radius:5px;background:#fff;padding:9px 12px;color:#263d55;font:inherit}.student-scan-button{border:0;border-radius:5px;background:#176da8;color:#fff;padding:10px 16px;font-weight:600}.student-table-wrap{width:100%;overflow:auto}.student-table{width:100%;border-collapse:collapse;font-size:13px;white-space:nowrap}.student-table th{font-weight:700;color:#526273;background:#f5f8fa;text-align:left;padding:12px}.student-table td{padding:14px 12px;border-bottom:1px solid #e9eef2;color:#344353}.student-attendance-date{font-weight:600}.student-attendance-mark{font-size:8px;color:#1585bf;margin-right:9px}.student-status-icon{color:#21834b;font-size:18px}@media(max-width:760px){.student-page{padding:22px 18px}.student-attendance-toolbar{align-items:stretch;flex-direction:column}.student-attendance-toolbar select{width:100%}}@media(max-width:560px){.student-page{padding:18px 12px}.student-card{padding:14px}.student-page-header h1{font-size:24px}}
</style>
<style>
#displayContainer:has(> .student-page){padding:50px}.student-page{width:100%;max-width:none;margin:0;padding:0}
</style>
@php
    $sampleAttendanceRecords = [
        ['number' => 1, 'date' => 'AUGUST 4, 2026 - TUES', 'coach' => 'Danica Reyes', 'level' => 'Beginner', 'log' => 'ON TIME', 'in' => '8:04 AM', 'out' => '9:12 AM'],
        ['number' => 2, 'date' => 'AUGUST 11, 2026 - TUES', 'coach' => 'Danica Reyes', 'level' => 'Beginner', 'log' => 'ON TIME', 'in' => '8:07 AM', 'out' => '9:02 AM'],
        ['number' => 2, 'date' => 'AUGUST 13, 2026 - THUR', 'coach' => 'Froy Bartolome', 'level' => 'Beginner', 'log' => 'ON TIME', 'in' => '7:56 AM', 'out' => '9:17 AM'],
    ];
@endphp

<main class="student-page student-attendance-page">
    <header class="student-page-header">
        <h1>Attendance</h1>
        <p>Check your attendance records</p>
    </header>

    <section class="student-card" aria-label="Attendance records">
        <div class="student-attendance-toolbar">
            <label for="studentAttendanceClass">
                Select Class
                <select id="studentAttendanceClass" name="class">
                    <option>Beginner</option>
                    <option>Intermediate</option>
                    <option>Advanced</option>
                </select>
            </label>
            <button class="btn btn-primary btn-icon-label" type="button"><i class="bi bi-qr-code-scan" aria-hidden="true"></i>Scan QR</button>
        </div>

        <div class="student-table-wrap">
            <table class="student-table student-attendance-table">
                <thead>
                    <tr><th>No.</th><th>Date</th><th>Coach</th><th>Course Level</th><th>Log</th><th>Time In</th><th>Time Out</th><th>Status</th></tr>
                </thead>
                <tbody>
                    @foreach ($sampleAttendanceRecords as $record)
                        <tr>
                            <td>{{ $record['number'] }}</td>
                            <td class="student-attendance-date"><i class="bi bi-circle-fill student-attendance-mark" aria-hidden="true"></i>{{ $record['date'] }}</td>
                            <td>{{ $record['coach'] }}</td>
                            <td>{{ $record['level'] }}</td>
                            <td>{{ $record['log'] }}</td>
                            <td>{{ $record['in'] }}</td>
                            <td>{{ $record['out'] }}</td>
                            <td><i class="bi bi-check-circle-fill student-status-icon" aria-label="Present"></i></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
</main>
@else
    ATTENDANCE NI STUDENT
@endif
