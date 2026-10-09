@if ((int) Auth::user()->role_id === 3)
<style>
.student-page{width:100%;max-width:1320px;margin:0 auto;padding:28px 32px 40px;color:#202b36}.student-page-header{margin:0 0 24px}.student-page-header h1{font-size:28px;font-weight:700;margin:0 0 5px;color:#173b63}.student-page-header p{margin:0;color:#657382;font-size:14px}.student-card{background:#fff;border:1px solid #dce3ea;border-radius:8px;padding:20px 22px;box-shadow:0 2px 8px rgba(26,51,77,.06)}.student-certificate-empty{min-height:280px;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center}.student-certificate-empty>i{font-size:46px;color:#1782bd;margin-bottom:14px}.student-certificate-empty strong{font-size:17px;color:#263d55}.student-certificate-empty p{max-width:440px;margin:8px 0 0;color:#657382;font-size:14px}@media(max-width:560px){.student-page{padding:18px 12px}.student-card{padding:16px}.student-page-header h1{font-size:24px}}
</style>
<style>
#displayContainer:has(> .student-page){padding:50px}.student-page{width:100%;max-width:none;margin:0;padding:0}
</style>
<main class="student-page student-certificates-page">
    <header class="student-page-header">
        <h1>Certificates</h1>
        <p>View and download your graduated achievements</p>
    </header>

    <section class="student-card student-certificate-empty" aria-label="Certificates">
        <i class="bi bi-award-fill" aria-hidden="true"></i>
        <strong>No certificates yet.</strong>
        <p>Complete a course to earn one. Track your milestones and level advancements here.</p>
    </section>
</main>
@else
    CERTIFICATES NI STUDENT
@endif
