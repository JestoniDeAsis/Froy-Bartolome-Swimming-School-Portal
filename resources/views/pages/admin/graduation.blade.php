<style>
    .graduation-page,
    .assessment-modal {
        --graduation-action-blue: #0757a5;
        --graduation-action-hover: #03447f;
        --graduation-button-stroke: #dce5ed;
    }

    .graduation-page {
        --graduation-ink: #172033;
        --graduation-muted: #667085;
        padding: 1.25rem;
        color: var(--graduation-ink);
    }

    .graduation-page .btn,
    .assessment-modal .btn {
        --bs-btn-focus-box-shadow: 0 0 0 .2rem rgba(100, 116, 139, .25);
    }

    .graduation-page .btn-primary,
    .assessment-modal .btn-primary {
        --bs-btn-bg: var(--graduation-action-blue);
        --bs-btn-border-color: var(--graduation-button-stroke);
        --bs-btn-hover-bg: var(--graduation-action-hover);
        --bs-btn-hover-border-color: var(--graduation-button-stroke);
        --bs-btn-active-bg: var(--graduation-action-hover);
        --bs-btn-active-border-color: var(--graduation-button-stroke);
        --bs-btn-disabled-bg: var(--graduation-action-blue);
        --bs-btn-disabled-border-color: var(--graduation-button-stroke);
    }

    .graduation-page .btn-outline-primary,
    .assessment-modal .btn-outline-primary {
        --bs-btn-color: var(--graduation-action-blue);
        --bs-btn-border-color: var(--graduation-button-stroke);
        --bs-btn-hover-bg: #f4f7f6;
        --bs-btn-hover-color: var(--graduation-action-blue);
        --bs-btn-hover-border-color: var(--graduation-button-stroke);
        --bs-btn-active-bg: var(--graduation-action-blue);
        --bs-btn-active-color: #fff;
        --bs-btn-active-border-color: var(--graduation-button-stroke);
    }

    .graduation-heading {
        padding: .15rem .25rem 1rem;
        margin-bottom: .85rem;
        border-bottom: 1px solid #d4d8de;
    }

    .graduation-heading h1 {
        margin: 0;
        font-size: 1.35rem;
        font-weight: 700;
        letter-spacing: -.02em;
    }

    .graduation-breadcrumb {
        margin-top: .3rem;
        color: var(--graduation-muted);
        font-size: .75rem;
        letter-spacing: .045em;
        text-transform: uppercase;
    }

    .graduation-breadcrumb span { color: var(--fbss-blue); font-weight: 700; }

    .graduation-tabs {
        display: flex;
        gap: .55rem;
        margin: 0 0 1.5rem;
    }

    .graduation-tabs .btn-tab {
        min-width: 145px;
        padding: .55rem 1rem;
        font-size: .75rem;
        font-weight: 700;
        text-transform: uppercase;
    }

    .graduation-tabs .btn-tab:not(.active) {
        --bs-btn-color: var(--graduation-ink);
        --bs-btn-border-color: var(--graduation-button-stroke);
        --bs-btn-hover-bg: #f4f7f6;
        --bs-btn-hover-color: var(--graduation-ink);
        --bs-btn-hover-border-color: var(--graduation-button-stroke);
    }

    .graduation-tabs .btn-tab.active {
        --bs-btn-bg: var(--graduation-action-blue);
        --bs-btn-border-color: var(--graduation-button-stroke);
        --bs-btn-hover-bg: var(--graduation-action-hover);
        --bs-btn-hover-border-color: var(--graduation-button-stroke);
        --bs-btn-active-bg: var(--graduation-action-hover);
        --bs-btn-active-border-color: var(--graduation-button-stroke);
    }

    .graduation-panel {
        max-width: 1100px;
        margin: 0 auto;
        padding: 1rem 1.2rem 1.25rem;
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        box-shadow: 0 1px 2px rgba(16, 24, 40, .05);
    }

    .graduation-panel[hidden] { display: none !important; }

    .graduation-panel-heading {
        display: flex;
        align-items: center;
        gap: .55rem;
        margin: 0 0 1rem;
        font-size: 1.05rem;
        font-weight: 700;
    }

    .graduation-panel-heading i { color: var(--fbss-blue); font-size: 1.2rem; }
    .graduation-panel-heading .group-count { color: var(--graduation-muted); font-size: .9rem; font-weight: 500; }

    .graduation-student-list { display: grid; gap: .65rem; padding-left: 1.2rem; }
    .graduation-empty { color: var(--graduation-muted); text-align: center; }

    .graduation-student {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        min-height: 54px;
        padding: .55rem .8rem;
        background: #e1f6ff;
        border: 1px solid rgba(16, 24, 40, .06);
        border-radius: 8px;
        box-shadow: 0 1px 1px rgba(16, 24, 40, .12);
    }

    .graduation-student--ready { background: #fbffe5; }
    .graduation-student--graduated { background: #e3ffe6; }
    .graduation-student-info { min-width: 0; }
    .graduation-student-name { font-size: .92rem; font-weight: 650; }
    .graduation-student-meta { color: var(--graduation-muted); font-size: .8rem; }
    .graduation-student-meta strong { font-weight: 700; font-style: italic; text-transform: uppercase; }
    .graduation-student .btn { min-width: 76px; }

    .graduation-ready-actions {
        display: grid;
        grid-template-columns: minmax(150px, 1fr) 92px;
        align-items: center;
        gap: .2rem .8rem;
        flex-shrink: 0;
    }

    .graduation-ready-session {
        grid-column: 1;
        color: var(--graduation-muted);
        font-size: .75rem;
        text-align: right;
        white-space: nowrap;
    }

    .graduation-ready-upload {
        grid-column: 1;
        color: var(--fbss-blue);
        cursor: pointer;
        font-size: .78rem;
        text-align: right;
        text-decoration: none;
    }

    .graduation-ready-upload:hover { color: var(--fbss-blue-hover); text-decoration: underline; }
    .graduation-ready-upload input { position: absolute; width: 1px; height: 1px; opacity: 0; }
    .graduation-ready-file { grid-column: 1 / -1; color: var(--graduation-muted); font-size: .68rem; text-align: right; }
    .graduation-ready-graduate { grid-column: 2; grid-row: 1; }
    .graduation-ready-view { grid-column: 2; grid-row: 2; }

    .graduation-graduate-actions {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 1rem;
        flex-shrink: 0;
    }

    .graduation-certificate-number { min-width: 110px; font-size: .75rem; font-weight: 600; text-align: right; white-space: nowrap; }

    .assessment-modal .modal-dialog { max-width: 410px; }
    .assessment-modal .modal-content { border: 0; border-radius: 12px; box-shadow: 0 18px 50px rgba(0, 0, 0, .18); }
    .assessment-modal .modal-header { padding: .9rem 1rem .5rem; border: 0; }
    .assessment-modal .modal-body { padding: 0 1rem .7rem; }
    .assessment-modal .modal-footer { padding: .6rem 1rem 1rem; border: 0; }
    .assessment-student-name { margin: 0; font-size: 1.05rem; font-weight: 700; }
    .assessment-title { margin: .15rem 0 .5rem; font-size: .8rem; font-weight: 600; }
    .assessment-payment {
        padding: .2rem .5rem;
        color: var(--fbss-danger);
        background: var(--fbss-danger-tint);
        border-radius: 999px;
        font-size: .65rem;
        font-weight: 700;
    }
    .assessment-payment--paid { color: var(--fbss-success); background: var(--fbss-success-tint); }
    .assessment-progress-summary { display: flex; justify-content: space-between; gap: .5rem; margin-bottom: .25rem; font-size: .7rem; }
    .assessment-progress-count { font-weight: 600; }
    .assessment-progress-percent { color: var(--graduation-muted); }
    .assessment-progress { height: 5px; background: #e7ebf0; border-radius: 99px; }
    .assessment-progress .progress-bar { background: var(--fbss-blue); border-radius: 99px; }
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
        color: var(--graduation-ink);
        text-align: left;
        font-size: .72rem;
        font-weight: 700;
    }
    .assessment-category-toggle:hover { background: #eaf1f7; }
    .assessment-category-toggle .bi-chevron-down { transition: transform .15s ease; }
    .assessment-category-toggle[aria-expanded="true"] .bi-chevron-down { transform: rotate(180deg); }
    .assessment-category-count { margin-left: auto; color: var(--graduation-muted); font-size: .65rem; font-weight: 500; white-space: nowrap; }
    .assessment-skills { padding: .25rem 0 .15rem; }
    .assessment-skill { display: flex; align-items: center; justify-content: space-between; gap: .5rem; padding: .28rem 0; font-size: .68rem; }
    .assessment-result { padding: .08rem .38rem; border-radius: 3px; font-size: .57rem; font-weight: 700; }
    .assessment-result--passed { color: #087443; background: #d8f6e6; }
    .assessment-result--failed { color: #b42318; background: #fde4e2; }
    .assessment-balance { color: var(--fbss-danger); font-size: .85rem; font-style: italic; }
    .assessment-back { min-width: 76px; }
    .graduation-feedback { max-width: 1100px; margin: 0 auto 1rem; }

    @media (max-width: 640px) {
        .graduation-page { padding: .8rem; }
        .graduation-tabs { gap: .4rem; }
        .graduation-tabs .btn-tab { min-width: 0; flex: 1; padding: .55rem .4rem; font-size: .65rem; }
        .graduation-panel { padding: .9rem .75rem 1rem; }
        .graduation-student-list { padding-left: .15rem; }
        .graduation-student { align-items: flex-start; flex-direction: column; }
        .graduation-ready-actions { grid-template-columns: minmax(0, 1fr) 76px; gap: .25rem .5rem; width: 100%; }
        .graduation-ready-session, .graduation-ready-upload, .graduation-ready-file { white-space: normal; }
        .graduation-ready-session, .graduation-ready-upload { text-align: left; }
        .graduation-graduate-actions { gap: .5rem; }
        .graduation-ready-actions, .graduation-graduate-actions { width: 100%; }
        .graduation-graduate-actions { justify-content: space-between; }
        .graduation-certificate-number { min-width: 0; white-space: normal; }
    }
</style>

<div class="graduation-page">
    <div class="alert alert-info graduation-feedback d-none" id="graduationFeedback" role="status"></div>
    <header class="graduation-heading">
        <h1>Graduation &amp; Certificates</h1>
        <div class="graduation-breadcrumb"><span># Graduates</span> &middot; # Ready for assessment</div>
    </header>

    <div class="graduation-tabs btn-tab-group" role="tablist" aria-label="Graduation student groups">
        @foreach ($graduationTabs as $key => $tab)
            <button type="button" class="btn btn-outline-primary btn-tab {{ $key === 'ready' ? 'active' : '' }}" id="graduation-tab-{{ $key }}" role="tab" aria-controls="graduation-panel-{{ $key }}" aria-selected="{{ $key === 'ready' ? 'true' : 'false' }}" data-graduation-tab="{{ $key }}">
                {{ $tab['label'] }}
            </button>
        @endforeach
    </div>

    @foreach ($graduationTabs as $key => $tab)
        <section class="graduation-panel" id="graduation-panel-{{ $key }}" role="tabpanel" aria-labelledby="graduation-tab-{{ $key }}" {{ $key === 'ready' ? '' : 'hidden' }}>
            <h2 class="graduation-panel-heading">
                <i class="bi {{ $tab['icon'] }}" aria-hidden="true"></i>
                {{ $tab['label'] }} <span class="group-count">({{ count($tab['students']) }})</span>
            </h2>

            @if (count($tab['students']))
                <div class="graduation-student-list">
                    @foreach ($tab['students'] as $student)
                        <article class="graduation-student graduation-student--{{ $key }}">
                            <div class="graduation-student-info">
                                <div class="graduation-student-name">{{ $student['name'] }}</div>
                                <div class="graduation-student-meta"><strong>{{ $student['level'] }}</strong><span class="mx-2">&middot;</span><strong>{{ $student['sessions'] }}</strong></div>
                            </div>
                            @if ($key === 'ready')
                                <div class="graduation-ready-actions">
                                    <span class="graduation-ready-session">LAST SESSION: {{ $student['last_session'] ?? '—' }}</span>
                                    <button type="button" class="btn btn-primary btn-sm graduation-ready-graduate" data-bs-toggle="modal" data-bs-target="#assessmentModal" data-student="{{ $student['name'] }}" data-level="{{ $student['level'] }}" data-assessment-type="ready" data-assessment-mode="graduate">
                                        <i class="bi bi-award" aria-hidden="true"></i> Graduate
                                    </button>
                                    <label class="graduation-ready-upload" for="certificate-{{ $loop->index }}-{{ $key }}">
                                        <i class="bi bi-upload" aria-hidden="true"></i> Upload Certificate
                                        <input type="file" id="certificate-{{ $loop->index }}-{{ $key }}" accept=".pdf,image/*" data-certificate-input>
                                    </label>
                                    <button type="button" class="btn btn-primary btn-sm graduation-ready-view" data-bs-toggle="modal" data-bs-target="#assessmentModal" data-student="{{ $student['name'] }}" data-level="{{ $student['level'] }}" data-assessment-type="ready" data-assessment-mode="view">View</button>
                                    <span class="graduation-ready-file" data-certificate-name></span>
                                </div>
                            @elseif ($key === 'active')
                                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#assessmentModal" data-student="{{ $student['name'] }}" data-level="{{ $student['level'] }}" data-assessment-type="active" data-assessment-mode="view">View</button>
                            @else
                                <div class="graduation-graduate-actions">
                                    <span class="graduation-certificate-number">{{ $student['certificate'] }}</span>
                                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#assessmentModal" data-student="{{ $student['name'] }}" data-level="{{ $student['level'] }}" data-assessment-type="graduated" data-assessment-mode="view">View Details</button>
                                </div>
                            @endif
                        </article>
                    @endforeach
                </div>
            @else
                <div class="graduation-empty text-muted text-center py-4">No {{ strtolower($tab['label']) }} records found.</div>
            @endif
        </section>
    @endforeach
</div>

<div class="modal fade assessment-modal" id="assessmentModal" tabindex="-1" aria-labelledby="assessmentStudent" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h2 class="assessment-student-name" id="assessmentStudent">Maria P. Santos</h2>
                    <div class="text-muted small" id="assessmentLevel">Core Skills Assessment List</div>
                </div>
                <span class="assessment-payment ms-auto me-2">Unpaid</span>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="assessment-progress-summary">
                    <span class="assessment-progress-count"><span id="assessmentPassed">4</span> / <span id="assessmentTotal">18</span> passed</span>
                    <span class="assessment-progress-percent" id="assessmentPercent">22.7%</span>
                </div>
                <div class="progress assessment-progress" role="progressbar" aria-label="Skills passed" aria-valuenow="22.7" aria-valuemin="0" aria-valuemax="100">
                    <div class="progress-bar" style="width: 22.7%"></div>
                </div>

                <div class="assessment-categories">
                    <div class="assessment-category">
                        <button type="button" class="assessment-category-toggle" data-bs-toggle="collapse" data-bs-target="#skills-water" aria-expanded="true" aria-controls="skills-water">
                            <i class="bi bi-chevron-down" aria-hidden="true"></i><span>Water Comfort &amp; Safety</span><span class="assessment-category-count">2 / 4 passed</span>
                        </button>
                        <div class="collapse show" id="skills-water">
                            <div class="assessment-skills">
                                <div class="assessment-skill"><span>Entering and exiting the pool safely</span><span class="assessment-result assessment-result--passed">Passed</span></div>
                                <div class="assessment-skill"><span>Submerging face underwater comfortably</span><span class="assessment-result assessment-result--passed">Passed</span></div>
                                <div class="assessment-skill"><span>Floating on front (face in water)</span><span class="assessment-result assessment-result--failed">Failed</span></div>
                                <div class="assessment-skill"><span>Floating on back (supine position)</span><span class="assessment-result assessment-result--failed">Failed</span></div>
                            </div>
                        </div>
                    </div>

                    <div class="assessment-category">
                        <button type="button" class="assessment-category-toggle" data-bs-toggle="collapse" data-bs-target="#skills-breathing" aria-expanded="true" aria-controls="skills-breathing">
                            <i class="bi bi-chevron-down" aria-hidden="true"></i><span>Breathing Skills</span><span class="assessment-category-count">2 / 3 passed</span>
                        </button>
                        <div class="collapse show" id="skills-breathing">
                            <div class="assessment-skills">
                                <div class="assessment-skill"><span>Exhaling underwater (bubbles)</span><span class="assessment-result assessment-result--passed">Passed</span></div>
                                <div class="assessment-skill"><span>Inhaling above water</span><span class="assessment-result assessment-result--passed">Passed</span></div>
                                <div class="assessment-skill"><span>Rhythmic breathing while swimming</span><span class="assessment-result assessment-result--failed">Failed</span></div>
                            </div>
                        </div>
                    </div>

                    <div class="assessment-category">
                        <button type="button" class="assessment-category-toggle" data-bs-toggle="collapse" data-bs-target="#skills-arms" aria-expanded="true" aria-controls="skills-arms">
                            <i class="bi bi-chevron-down" aria-hidden="true"></i><span>Arm Pulls &amp; Kicking Fundamentals</span><span class="assessment-category-count">0 / 3 passed</span>
                        </button>
                        <div class="collapse show" id="skills-arms">
                            <div class="assessment-skills">
                                <div class="assessment-skill"><span>Rhythmic breathing while swimming</span><span class="assessment-result assessment-result--failed">Failed</span></div>
                                <div class="assessment-skill"><span>Rhythmic breathing while swimming</span><span class="assessment-result assessment-result--failed">Failed</span></div>
                                <div class="assessment-skill"><span>Rhythmic breathing while swimming</span><span class="assessment-result assessment-result--failed">Failed</span></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer justify-content-between">
                <strong class="assessment-balance">Balance: ₱3,000</strong>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-primary btn-sm assessment-back" data-bs-dismiss="modal">Back</button>
                    <button type="button" class="btn btn-primary btn-sm d-none" id="confirmGraduationButton" data-bs-dismiss="modal">Confirm</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const graduationTabs = document.querySelectorAll('[data-graduation-tab]');
        const assessmentModal = document.getElementById('assessmentModal');
        const paymentBadge = assessmentModal.querySelector('.assessment-payment');
        const passedCount = document.getElementById('assessmentPassed');
        const totalCount = document.getElementById('assessmentTotal');
        const percentage = document.getElementById('assessmentPercent');
        const progressBar = assessmentModal.querySelector('.assessment-progress .progress-bar');
        const progressTrack = assessmentModal.querySelector('.assessment-progress');
        const categoryCounts = assessmentModal.querySelectorAll('.assessment-category-count');
        const skillResults = assessmentModal.querySelectorAll('.assessment-result');
        const categoryToggles = assessmentModal.querySelectorAll('.assessment-category-toggle');
        const categoryPanels = assessmentModal.querySelectorAll('.assessment-category .collapse');
        const balance = assessmentModal.querySelector('.assessment-balance');
        const confirmGraduationButton = document.getElementById('confirmGraduationButton');
        const graduationFeedback = document.getElementById('graduationFeedback');

        const defaultAssessment = {
            payment: paymentBadge.textContent.trim(),
            passed: passedCount.textContent,
            total: totalCount.textContent,
            percentage: percentage.textContent,
            balance: balance.textContent,
            categoryCounts: Array.from(categoryCounts, (count) => count.textContent),
            skillResults: Array.from(skillResults, (result) => result.textContent),
        };

        graduationTabs.forEach((tab) => {
            tab.addEventListener('click', () => {
                graduationTabs.forEach((item) => {
                    const isSelected = item === tab;
                    item.classList.toggle('active', isSelected);
                    item.setAttribute('aria-selected', String(isSelected));
                });

                document.querySelectorAll('.graduation-panel').forEach((panel) => {
                    panel.hidden = panel.id !== tab.getAttribute('aria-controls');
                });
            });
        });

        assessmentModal.addEventListener('show.bs.modal', (event) => {
            const trigger = event.relatedTarget;
            const displayName = trigger.dataset.student || 'Student';
            const assessmentType = trigger.dataset.assessmentType || 'active';
            const assessmentMode = trigger.dataset.assessmentMode || 'view';
            const nameParts = displayName.split(', ');
            const modalName = nameParts.length === 2 ? `${nameParts[1]} ${nameParts[0]}` : displayName;

            document.getElementById('assessmentStudent').textContent = modalName;
            document.getElementById('assessmentLevel').textContent = 'Core Skills Assessment List';
            confirmGraduationButton.classList.toggle('d-none', !(assessmentType === 'ready' && assessmentMode === 'graduate'));

            if (assessmentType === 'ready' || assessmentType === 'graduated') {
                paymentBadge.textContent = 'Paid';
                paymentBadge.classList.add('assessment-payment--paid');
                passedCount.textContent = '10';
                totalCount.textContent = '10';
                percentage.textContent = '100%';
                progressBar.style.width = '100%';
                progressTrack.setAttribute('aria-valuenow', '100');
                balance.textContent = 'Balance: ₱0';
                ['4 / 4 passed', '3 / 3 passed', '3 / 3 passed'].forEach((count, index) => {
                    categoryCounts[index].textContent = count;
                });
                skillResults.forEach((result) => {
                    result.textContent = 'Passed';
                    result.classList.remove('assessment-result--failed');
                    result.classList.add('assessment-result--passed');
                });
                categoryPanels.forEach((panel, index) => {
                    const isExpanded = assessmentType === 'graduated' || assessmentMode === 'view';
                    panel.classList.toggle('show', isExpanded);
                    categoryToggles[index].setAttribute('aria-expanded', String(isExpanded));
                    categoryToggles[index].querySelector('i').className = `bi ${isExpanded ? 'bi-chevron-down' : 'bi-chevron-right'}`;
                });
                return;
            }

            paymentBadge.textContent = defaultAssessment.payment;
            paymentBadge.classList.remove('assessment-payment--paid');
            passedCount.textContent = defaultAssessment.passed;
            totalCount.textContent = defaultAssessment.total;
            percentage.textContent = defaultAssessment.percentage;
            progressBar.style.width = `${defaultAssessment.percentage}`;
            progressTrack.setAttribute('aria-valuenow', '22.7');
            balance.textContent = defaultAssessment.balance;
            categoryCounts.forEach((count, index) => {
                count.textContent = defaultAssessment.categoryCounts[index];
            });
            skillResults.forEach((result, index) => {
                const isPassed = defaultAssessment.skillResults[index] === 'Passed';
                result.textContent = defaultAssessment.skillResults[index];
                result.classList.toggle('assessment-result--passed', isPassed);
                result.classList.toggle('assessment-result--failed', !isPassed);
            });
            categoryPanels.forEach((panel, index) => {
                panel.classList.add('show');
                categoryToggles[index].setAttribute('aria-expanded', 'true');
                categoryToggles[index].querySelector('i').className = 'bi bi-chevron-down';
            });
        });

        document.querySelectorAll('[data-certificate-input]').forEach((input) => {
            input.addEventListener('change', () => {
                const fileName = input.files && input.files.length ? `Selected: ${input.files[0].name} (not uploaded)` : '';
                input.closest('.graduation-ready-actions').querySelector('[data-certificate-name]').textContent = fileName;
            });
        });

        confirmGraduationButton.addEventListener('click', () => {
            graduationFeedback.textContent = 'Graduation confirmed in this preview only. No student record was changed.';
            graduationFeedback.classList.remove('d-none');
        });
    });
</script>
