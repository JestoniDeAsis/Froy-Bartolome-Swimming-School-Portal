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
                    </div>
                </article>
            @endforeach
        </div>

    @endif

</section>