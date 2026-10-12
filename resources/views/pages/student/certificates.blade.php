@if ((int) Auth::user()->role_id === 3)
<style>
.student-page{width:100%;max-width:1320px;margin:0 auto;padding:28px 32px 40px;color:#202b36}.student-page-header{margin:0 0 24px}.student-page-header h1{font-size:28px;font-weight:700;margin:0 0 5px;color:#173b63}.student-page-header p{margin:0;color:#657382;font-size:14px}.student-card{background:#fff;border:1px solid #dce3ea;border-radius:8px;padding:20px 22px;box-shadow:0 2px 8px rgba(26,51,77,.06)}.student-certificate-empty{min-height:280px;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center}.student-certificate-empty>i{font-size:46px;color:#1782bd;margin-bottom:14px}.student-certificate-empty strong{font-size:17px;color:#263d55}.student-certificate-empty p{max-width:440px;margin:8px 0 0;color:#657382;font-size:14px}@media(max-width:560px){.student-page{padding:18px 12px}.student-card{padding:16px}.student-page-header h1{font-size:24px}}
.student-mode-switch{display:flex;align-items:center;justify-content:space-between;gap:1rem;margin:-8px 0 20px;padding:12px 14px;background:#fff;border:1px solid #dee2e6;border-radius:var(--fbss-radius,.5rem)}
.student-mode-label{margin:0;color:#495057;font-size:.875rem;font-weight:600}
.student-mode-help{margin:0;color:#6c757d;font-size:.75rem}
.student-applicant-certificate{min-height:280px;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center}
.student-applicant-certificate>i{margin-bottom:14px;color:var(--fbss-warning,#ffc107);font-size:58px}
.student-applicant-certificate strong{font-size:17px;color:#263d55}
.student-applicant-certificate p{max-width:440px;margin:8px 0 0;color:#657382;font-size:14px}
@media(max-width:560px){.student-mode-switch{align-items:flex-start;flex-direction:column;margin-top:0}.student-mode-switch .btn-tab-group{width:100%}.student-mode-switch .btn-tab{flex:1}}
</style>
<style>
#displayContainer:has(> .student-page){padding:50px}.student-page{width:100%;max-width:none;margin:0;padding:0}
</style>
<main class="student-page student-certificates-page">
    <header class="student-page-header">
        <h1>Certificates</h1>
        <p id="studentCertificatesRole">Swimmer — Applicant</p>
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

    <section class="student-card student-applicant-certificate" data-student-mode-panel="applicant" aria-label="Applicant certificate eligibility">
        <i class="bi bi-award-fill" aria-hidden="true"></i>
        <strong>Not yet eligible</strong>
        <p>Complete your Beginner Swim Course and pass the final assessment to earn your official certificate.</p>
    </section>

    <section class="student-card student-certificate-empty" data-student-mode-panel="student" aria-label="Student certificates" hidden>
        <i class="bi bi-award-fill" aria-hidden="true"></i>
        <strong>No certificates yet.</strong>
        <p>Complete a course to earn one. Track your milestones and level advancements here.</p>
    </section>
</main>
<script>
(() => {
    const buttons = document.querySelectorAll('[data-student-mode-button]');
    const panels = document.querySelectorAll('[data-student-mode-panel]');
    const roleLabel = document.getElementById('studentCertificatesRole');

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
    CERTIFICATES NI STUDENT
@endif
