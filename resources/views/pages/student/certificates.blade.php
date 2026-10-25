@extends('layouts.toast')

@php
    // Data comes from CertificateController::pageData(). The page is rendered by
    // name from role_menus.php (no route), so it is called here.
    $certificates = app(\App\Http\Controllers\CertificateController::class)->pageData(auth()->user())['certificates'];
@endphp

<style>
/* Page-specific layout only. Typography, buttons and the base look come from
   default.css / our_theme.css. */

.certificatesPage {
    width: 100%;
    padding: 1.25rem;
    box-sizing: border-box;
}

.certificatesHeader { margin-bottom: 1rem; }

.certificatesGrid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1rem;
}

.certificateCard {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    padding: 1.1rem 1.25rem;
    background: #fff;
    border: 1px solid #e7ebef;
    border-radius: 12px;
    transition: box-shadow .15s ease, border-color .15s ease;
}

.certificateCard:hover {
    border-color: #bee3f8;
    box-shadow: 0 6px 16px rgba(20, 30, 50, .08);
}

.certificateTop { display: flex; align-items: flex-start; gap: .85rem; }

.certificateIcon {
    flex-shrink: 0;
    display: grid;
    place-items: center;
    width: 3rem;
    height: 3rem;
    border-radius: 50%;
    background: var(--fbss-success-tint, #e6f7ec);
    color: var(--fbss-success, #1a7f3c);
    font-size: 1.4rem;
}

.certificateCourse { margin: 0 0 .2rem; color: #1f2937; font-size: 1.05rem; font-weight: 700; }
.certificateMeta { margin: 0; color: #667085; font-size: .8125rem; }
.certificateFile {
    margin: .45rem 0 0;
    color: #667085;
    font-size: .75rem;
    overflow-wrap: anywhere;
}

.certificateFile i { margin-right: .3rem; color: #c22b3a; }

.certificateActions { display: flex; flex-wrap: wrap; gap: .5rem; margin-top: auto; }

.certificatesEmpty {
    max-width: 640px;
    margin: 1.5rem auto 0;
    padding: 2.25rem 1.5rem;
    background: #fff;
    border: 1px solid #e7ebef;
    border-radius: 12px;
    text-align: center;
}

.certificatesEmptyIcon {
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

.certificatesEmpty h3 { margin: 0 0 .4rem; color: #1f2937; font-size: 1.2rem; font-weight: 700; }
.certificatesEmpty p { margin: 0; color: #667085; font-size: .9rem; }

/* Skill assessment popup (same look as the admin Graduation popup) */
.assessment-modal .modal-dialog { max-width: 410px; }
.assessment-modal .modal-content { border: 0; border-radius: 12px; box-shadow: 0 18px 50px rgba(0, 0, 0, .18); }
.assessment-modal .modal-header { padding: .9rem 1rem .5rem; border: 0; }
.assessment-modal .modal-body { padding: 0 1rem .7rem; }
.assessment-modal .modal-footer { padding: .6rem 1rem 1rem; border: 0; }
.assessment-student-name { margin: 0; font-size: 1.05rem; font-weight: 700; }
.assessment-progress-summary { display: flex; justify-content: space-between; gap: .5rem; margin-bottom: .25rem; font-size: .7rem; }
.assessment-progress-count { font-weight: 600; }
.assessment-progress-percent { color: #667085; }
.assessment-progress { height: 5px; background: #e7ebf0; border-radius: 99px; }
.assessment-progress .progress-bar { background: var(--fbss-blue, #0d6efd); border-radius: 99px; }
.assessment-categories { display: grid; gap: .35rem; margin-top: 1rem; }
.assessment-category { border-radius: 7px; overflow: hidden; }
.assessment-category-toggle {
    display: flex;
    align-items: center;
    width: 100%;
    gap: .45rem;
    padding: .45rem .55rem;
    border: 0;
    background: #f4f7fa;
    color: #172033;
    text-align: left;
    font-size: .72rem;
    font-weight: 700;
}
.assessment-category-toggle:hover { background: #eaf1f7; }
.assessment-category-toggle .bi-chevron-down { transition: transform .15s ease; }
.assessment-category-toggle[aria-expanded="true"] .bi-chevron-down { transform: rotate(180deg); }
.assessment-category-count { margin-left: auto; color: #667085; font-size: .65rem; font-weight: 500; white-space: nowrap; }
.assessment-skills { padding: .25rem 0 .15rem; }
.assessment-skill { display: flex; align-items: center; justify-content: space-between; gap: .5rem; padding: .28rem 0; font-size: .68rem; }
.assessment-result { padding: .08rem .38rem; border-radius: 3px; font-size: .57rem; font-weight: 700; }
.assessment-result--passed { color: #087443; background: #d8f6e6; }
.assessment-result--failed { color: #b42318; background: #fde4e2; }
.assessment-result--none { color: #667085; background: #f1f3f5; }
.assessment-note { margin: 0 0 .6rem; font-size: .72rem; color: #667085; }
.assessment-back { min-width: 76px; }

@media (max-width: 575.98px) {
    .certificatesPage { padding: 1rem; }
    .certificateActions .btn { flex: 1 1 auto; }
}
</style>

<section class="certificatesPage" id="certificatesPage" aria-labelledby="certificatesTitle">

    <header class="certificatesHeader">
        <h2 class="mb-1" id="certificatesTitle">Certificates</h2>
        <p class="text-muted mb-0">Your graduation certificates from Froy Bartolome Swimming School.</p>
    </header>

    @if (empty($certificates))

        {{-- Nothing to show yet --}}
        <div class="certificatesEmpty" id="certificatesEmpty">
            <div class="certificatesEmptyIcon"><i class="bi bi-award" aria-hidden="true"></i></div>
            <h3>No certificates yet.</h3>
            <p>Complete a course to earn one. Your certificate will appear here after you graduate.</p>
        </div>

    @else

        <div class="certificatesGrid" id="certificatesGrid">
            @foreach ($certificates as $certificate)
                <article class="certificateCard">
                    <div class="certificateTop">
                        <div class="certificateIcon"><i class="bi bi-award-fill" aria-hidden="true"></i></div>
                        <div>
                            <h3 class="certificateCourse">{{ $certificate['course'] }}</h3>
                            @if ($certificate['graduatedAt'])<p class="certificateMeta">Graduated {{ $certificate['graduatedAt'] }}</p>@endif
                            @if ($certificate['enrolledAt'])<p class="certificateMeta">Enrolled {{ $certificate['enrolledAt'] }}</p>@endif
                            <p class="certificateFile"><i class="bi bi-file-earmark-pdf" aria-hidden="true"></i>{{ $certificate['fileName'] }}</p>
                        </div>
                    </div>
                    <div class="certificateActions">
                        <a class="btn btn-primary btn-sm" href="{{ url('/certificates/' . $certificate['id']) }}" target="_blank" rel="noopener">
                            <i class="bi bi-eye" aria-hidden="true"></i> View
                        </a>
                        <a class="btn btn-outline-primary btn-sm" href="{{ url('/certificates/' . $certificate['id']) }}?download=1">
                            <i class="bi bi-download" aria-hidden="true"></i> Download
                        </a>
                        @if ($certificate['assessment'])
                            <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#assessmentModal" data-certificate-id="{{ $certificate['id'] }}" data-course="{{ $certificate['course'] }}">
                                <i class="bi bi-clipboard-check" aria-hidden="true"></i> Skills
                            </button>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>

    @endif

</section>

@if (collect($certificates)->contains(fn ($c) => $c['assessment']))
<div class="modal fade assessment-modal" id="assessmentModal" tabindex="-1" aria-labelledby="assessmentCourse" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h2 class="assessment-student-name" id="assessmentCourse">Course</h2>
                    <div class="text-muted small">Core Skills Assessment List</div>
                    <div class="text-muted small" id="assessmentMeta"></div>
                </div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="assessment-progress-summary">
                    <span class="assessment-progress-count"><span id="assessmentPassed">0</span> / <span id="assessmentTotal">0</span> passed</span>
                    <span class="assessment-progress-percent" id="assessmentPercent">0%</span>
                </div>
                <div class="progress assessment-progress" role="progressbar" aria-label="Skills passed" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                    <div class="progress-bar" style="width: 0%"></div>
                </div>
                <div class="assessment-categories" id="assessmentCategories"></div>
            </div>
            <div class="modal-footer justify-content-end">
                <button type="button" class="btn btn-outline-primary btn-sm assessment-back" data-bs-dismiss="modal">Back</button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    // Skill assessment of each certificate, keyed by enrollment id (from CertificateController::pageData()).
    const ASSESSMENTS = @json(collect($certificates)->filter(fn ($c) => $c['assessment'])->mapWithKeys(fn ($c) => [$c['id'] => $c['assessment']]));

    const modalEl = document.getElementById('assessmentModal');
    if (!modalEl) return;

    const progressBar = modalEl.querySelector('.assessment-progress .progress-bar');
    const progressTrack = modalEl.querySelector('.assessment-progress');
    const categoriesEl = document.getElementById('assessmentCategories');

    const RESULT = {
        pass: { cls: 'assessment-result--passed', text: 'Passed' },
        failed: { cls: 'assessment-result--failed', text: 'Failed' },
        none: { cls: 'assessment-result--none', text: 'Not marked' }
    };

    function make(tag, className, text) {
        const el = document.createElement(tag);
        if (className) el.className = className;
        if (text !== undefined) el.textContent = text;
        return el;
    }

    function renderCategories(categories) {
        categoriesEl.replaceChildren();

        if (!categories.length) {
            categoriesEl.append(make('p', 'assessment-note', 'No skill categories have been set up for this course.'));
            return;
        }

        categories.forEach((category, index) => {
            const panelId = `assessment-skills-${index}`;
            const wrapper = make('div', 'assessment-category');

            const toggle = make('button', 'assessment-category-toggle');
            toggle.type = 'button';
            toggle.setAttribute('data-bs-toggle', 'collapse');
            toggle.setAttribute('data-bs-target', `#${panelId}`);
            toggle.setAttribute('aria-expanded', 'true');
            toggle.setAttribute('aria-controls', panelId);

            const chevron = make('i', 'bi bi-chevron-down');
            chevron.setAttribute('aria-hidden', 'true');
            toggle.append(
                chevron,
                make('span', null, category.name),
                make('span', 'assessment-category-count', `${category.passed} / ${category.total} passed`)
            );

            const panel = make('div', 'collapse show');
            panel.id = panelId;
            const skills = make('div', 'assessment-skills');

            category.skills.forEach(skill => {
                const result = RESULT[skill.result] || RESULT.none;
                const row = make('div', 'assessment-skill');
                row.append(make('span', null, skill.name), make('span', `assessment-result ${result.cls}`, result.text));
                skills.append(row);
            });

            panel.append(skills);
            wrapper.append(toggle, panel);
            categoriesEl.append(wrapper);
        });
    }

    modalEl.addEventListener('show.bs.modal', event => {
        const trigger = event.relatedTarget;
        const detail = ASSESSMENTS[trigger.dataset.certificateId];
        if (!detail) return;

        document.getElementById('assessmentCourse').textContent = trigger.dataset.course || 'Course';
        document.getElementById('assessmentMeta').textContent = detail.assessedBy
            ? `Assessed by ${detail.assessedBy}${detail.assessedAt ? ' on ' + detail.assessedAt : ''}`
            : '';

        const percent = detail.total > 0 ? Math.round((detail.passed / detail.total) * 1000) / 10 : 0;
        document.getElementById('assessmentPassed').textContent = detail.passed;
        document.getElementById('assessmentTotal').textContent = detail.total;
        document.getElementById('assessmentPercent').textContent = `${percent}%`;
        progressBar.style.width = `${percent}%`;
        progressTrack.setAttribute('aria-valuenow', String(percent));

        renderCategories(detail.categories || []);
    });
})();
</script>
@endif