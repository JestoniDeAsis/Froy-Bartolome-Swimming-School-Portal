@extends('layouts.toast')

<style>
/* Typography, inputs, focus states, and primary buttons inherit from default.css. */
/* Page layout: announcement cards use the same full content width as Courses. */
.announcements-container {
  width: 100%;
  padding: 1.25rem;
}

.announcements-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 1rem;
}

/* Filter bar: lets admins narrow the scrollable list to one audience.
   Uses the shared .btn-tab-group / .btn-tab classes (see Sales & Invoice)
   for the tab look; only the count badge is announcement-specific. */
.announcements-filter-bar {
  margin-bottom: 0.75rem;
}

.announcements-filter-count {
  margin-left: 0.35rem;
  opacity: 0.75;
}

/* Scroll region: keeps the list contained to a fixed viewport as it grows. */
.announcements-scroll {
  max-height: 520px;
  overflow-y: auto;
  padding-right: 0.25rem;
}

.announcements-list {
  display: grid;
  gap: 0.75rem;
}

/* Cards are clickable (opens the view popup); action buttons stop propagation. */
.announcement-card {
  display: grid;
  grid-template-columns: auto 1fr auto;
  gap: 0.75rem;
  padding: 1rem;
  background: #ffffff;
  border: 1px solid #E7EBEF;
  border-radius: 10px;
  cursor: pointer;
  transition: box-shadow 0.15s ease, border-color 0.15s ease;
}

.announcement-card:hover,
.announcement-card:focus-visible {
  border-color: #BEE3F8;
  box-shadow: 0 6px 16px rgba(20, 30, 50, 0.08);
  outline: 0;
}

.announcement-icon {
  color: #0093E5;
  font-size: 1rem;
  line-height: 1.5;
}

.announcement-title {
  margin: 0 0 0.25rem;
  color: #1F2937;
  font-size: 0.9375rem;
  font-weight: 600;
}

/* Fixed-height preview; a full popup shows the untruncated message. */
.announcement-message-wrap {
  position: relative;
  max-height: 3.2rem;
  overflow: hidden;
}

.announcement-message-wrap::after {
  content: '';
  position: absolute;
  inset: auto 0 0 0;
  height: 1.5rem;
  background: linear-gradient(to bottom, rgba(255, 255, 255, 0), #ffffff);
}

.announcement-message {
  margin: 0;
  color: #495057;
  font-size: 0.875rem;
  font-weight: 400;
  line-height: 1.5;
  /* Preserve line breaks and repeated spaces exactly as stored. */
  white-space: pre-wrap;
}

.announcement-read-more {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  margin-top: 0.4rem;
  padding: 0;
  border: 0;
  background: none;
  color: #0093E5;
  font-size: 0.8125rem;
  font-weight: 600;
}

.announcement-read-more:hover,
.announcement-read-more:focus-visible {
  color: #045199;
  outline: 0;
  text-decoration: underline;
}

.announcement-meta {
  display: flex;
  flex-wrap: wrap;
  justify-content: flex-start;
  gap: 0.25rem 1rem;
  margin-top: 0.75rem;
  color: #6c757d;
  font-size: 0.75rem;
  font-weight: 500;
}

.announcement-actions {
  display: flex;
  align-self: start;
  gap: 0.4rem;
}

.announcement-recipient {
  color: #343A40;
  font-weight: 500;
}

.announcements-empty,
.announcements-loading,
.announcements-error {
  padding: 2rem;
  background: #ffffff;
  border: 1px solid #E7EBEF;
  border-radius: 10px;
  color: #6c757d;
  text-align: center;
}

.announcements-error {
  color: #E03131;
  border-color: #FFC9C9;
  background: #FFF5F5;
}

.announcements-error-retry {
  margin-top: 0.75rem;
}

/* Backdrop = shared .modalOverlay in our_theme.css; this is the dialog box inside it. */
.announcement-modal-dialog {
  width: min(620px, 100%);
  max-height: calc(100vh - 2.5rem);
  overflow: auto;
  background: #ffffff;
  border: 1px solid #E7EBEF;
  border-radius: 14px;
  box-shadow: 0 12px 32px rgba(20, 30, 50, 0.16);
}

.announcement-modal-header,
.announcement-modal-footer {
  padding: 1rem 1.25rem;
}

.announcement-modal-header {
  border-bottom: 1px solid #E7EBEF;
}

.announcement-modal-header h2 {
  font-size: 1.1rem;
}

.announcement-modal-body {
  padding: 1.25rem;
}

.announcement-modal-footer {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 0.5rem;
  border-top: 1px solid #E7EBEF;
}

.announcement-modal-footer .btn {
  min-width: 100px;
}

.announcement-required-mark {
  color: #E03131;
  margin-left: 0.15rem;
}

/* Recipient picker uses the shared .registerDropdown (our_theme.css + assets/js/fbss-dropdown.js). */
.registerDropdown.isPlaceholder .registerDropdownToggle span { color: #6c757d; }

/* Invalid state for a shared dropdown (set with .isInvalid on the .registerDropdown root). */
.registerDropdown.isInvalid .registerDropdownToggle { border-color: #dc3545; }
.registerDropdown.isInvalid .registerDropdownToggle:focus { box-shadow: 0 0 0 .2rem rgba(220, 53, 69, .15); }

.announcement-recipient-error {
  display: none;
  margin: 0.4rem 0 0;
  color: #E03131;
  font-size: 0.8125rem;
}

.announcement-recipient-error.is-visible {
  display: block;
}

/* View popup: a read-focused layout distinct from the create/edit form. */
.announcement-view-dialog {
  width: min(560px, 100%);
}

.announcement-view-header {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.announcement-view-top-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
}

.announcement-view-badge {
  display: inline-flex;
  align-items: center;
  padding: 0.2rem 0.65rem;
  border-radius: 999px;
  background: #e7f5ff;
  color: #045199;
  font-size: 0.75rem;
  font-weight: 600;
  letter-spacing: 0.02em;
  text-transform: uppercase;
}

.announcement-view-close {
  padding: 0;
  border: 0;
  background: none;
  color: #6c757d;
  font-size: 1.4rem;
  line-height: 1;
}

.announcement-view-close:hover,
.announcement-view-close:focus-visible {
  color: #212529;
  outline: 0;
}

.announcement-view-title {
  margin: 0;
  color: #1F2937;
  font-size: 1.25rem;
  font-weight: 700;
}

.announcement-view-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 0.25rem 1rem;
  color: #6c757d;
  font-size: 0.8125rem;
}

.announcement-view-meta i {
  margin-right: 0.3rem;
}

.announcement-view-message {
  margin: 0;
  color: #343A40;
  font-size: 0.95rem;
  line-height: 1.65;
  white-space: pre-wrap;
  word-break: break-word;
}

.announcement-view-footer {
  justify-content: space-between;
}

.announcement-view-footer-actions {
  display: flex;
  gap: 0.5rem;
}

@media (max-width: 575.98px) {
  .announcements-container {
    padding: 1rem;
  }

  .announcements-header {
    flex-direction: column;
    align-items: stretch;
    gap: 0.75rem;
  }

  #addAnnouncementButton {
    width: 100%;
    justify-content: center;
  }

  .announcements-filter-bar {
    display: grid !important;
    grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
    gap: 0.5rem !important;
    width: 100% !important;
    margin-bottom: 1rem !important;
  }

  .announcements-filter-bar .btn-tab {
    width: 100% !important;
    min-width: 0 !important;
    max-width: 100% !important;
    box-sizing: border-box !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    text-align: center !important;
    white-space: nowrap !important;
    padding: 0.45rem 0.4rem !important;
    font-size: 0.8125rem !important;
  }

  .announcements-scroll {
    max-height: 420px;
  }

  .announcement-card {
    grid-template-columns: auto 1fr;
  }

  .announcement-actions {
    grid-column: 2;
    justify-self: end;
  }

  .announcement-modal-footer {
    flex-wrap: wrap;
  }

  .announcement-view-footer {
    flex-direction: column-reverse;
    align-items: stretch;
  }

  .announcement-view-footer-actions {
    justify-content: flex-end;
  }
}
</style>

<!-- Main admin announcement workspace. -->
<section class="announcements-container" id="announcementsContainer" aria-labelledby="announcementsTitle">
    <header class="announcements-header" id="announcementsHeader">
        <div id="announcementsHeaderText">
            <h2 class="mb-1" id="announcementsTitle">Announcements</h2>
            <p class="text-muted mb-0" id="announcementsDescription">Send important messages to students or coaches.</p>
        </div>
        <button class="btn btn-primary" id="addAnnouncementButton" type="button">
            <i class="bi bi-plus-lg" id="addAnnouncementIcon"></i>
            New Announcement
        </button>
    </header>

    <!-- Filter tabs; renderAnnouncements() applies the active filter and updates counts. -->
    <nav class="announcements-filter-bar btn-tab-group" id="announcementsFilterBar" role="tablist" aria-label="Filter announcements by recipient"></nav>

    <!-- Reusable announcement cards are rendered into announcementsList by renderAnnouncements(). -->
    <div class="announcements-scroll" id="announcementsScroll">
        <div class="announcements-list" id="announcementsList"></div>
    </div>
</section>

<!-- View popup: read-only detail view, opened by clicking a card. -->
<div class="modalOverlay" id="announcementViewModal" hidden>
    <div class="announcement-modal-dialog announcement-view-dialog" id="announcementViewDialog" role="dialog" aria-modal="true" aria-labelledby="announcementViewTitle">
        <header class="announcement-modal-header announcement-view-header" id="announcementViewHeader">
            <div class="announcement-view-top-row">
                <span class="announcement-view-badge" id="announcementViewBadge">Everyone</span>
                <button class="announcement-view-close" id="closeAnnouncementViewButton" type="button" aria-label="Close">&times;</button>
            </div>
            <h2 class="announcement-view-title" id="announcementViewTitle"></h2>
            <div class="announcement-view-meta" id="announcementViewMeta">
                <span id="announcementViewPosted"><i class="bi bi-calendar-event"></i></span>
                <span id="announcementViewUpdated"><i class="bi bi-clock-history"></i></span>
            </div>
        </header>
        <div class="announcement-modal-body" id="announcementViewBody">
            <p class="announcement-view-message" id="announcementViewMessage"></p>
        </div>
        <footer class="announcement-modal-footer announcement-view-footer" id="announcementViewFooter">
            <div class="announcement-view-footer-actions">
                <button class="btn btn-outline-danger" id="viewDeleteButton" type="button">
                    <i class="bi bi-trash"></i> Delete
                </button>
                <button class="btn btn-outline-primary" id="viewEditButton" type="button">
                    <i class="bi bi-pencil-square"></i> Edit
                </button>
            </div>
            <button class="btn btn-primary" id="closeAnnouncementViewButtonFooter" type="button">Close</button>
        </footer>
    </div>
</div>

<!-- One modal reused for both new announcements and edits. -->
<div class="modalOverlay" id="announcementModal" hidden>
    <div class="announcement-modal-dialog" id="announcementModalDialog" role="dialog" aria-modal="true" aria-labelledby="announcementModalTitle">
        <header class="announcement-modal-header" id="announcementModalHeader">
            <h2 class="mb-1" id="announcementModalTitle">New Announcement</h2>
            <p class="text-muted mb-0" id="announcementModalDescription">Choose who should receive this message.</p>
        </header>

        <form id="announcementForm" novalidate>
            @csrf
            <div class="announcement-modal-body" id="announcementModalBody">
                <div class="mb-3" id="announcementTitleField">
                    <label class="form-label" id="announcementTitleLabel" for="announcementTitleInput">Title</label>
                    <input class="form-control" id="announcementTitleInput" name="announcement_title" type="text" maxlength="100" required>
                </div>
                <div class="mb-3" id="announcementMessageField">
                    <label class="form-label" id="announcementMessageLabel" for="announcementMessageInput">Message</label>
                    <textarea class="form-control" id="announcementMessageInput" name="announcement_message" rows="5" maxlength="1000" required></textarea>
                </div>
                <div id="announcementRecipientField">
                    <label class="form-label" id="announcementRecipientLabel" for="announcementRecipientToggle">Recipients<span class="announcement-required-mark" aria-hidden="true">*</span></label>
                    <!-- Shared dropdown; the hidden field supplies announcement_recipients_id to the form. -->
                    <div class="registerDropdown ddBlock isPlaceholder" id="announcementRecipientDropdown" data-fbss-dropdown>
                        <button class="registerDropdownToggle" id="announcementRecipientToggle" type="button" aria-expanded="false" aria-controls="announcementRecipientOptions" aria-required="true">
                            <span id="announcementRecipientSelected">Select a recipient</span>
                            <i class="bi bi-chevron-down biChevronDown" aria-hidden="true"></i>
                        </button>
                        <div class="registerDropdownOptions" id="announcementRecipientOptions" hidden>
                            <!-- hidden placeholder: lets the form be reset to "nothing chosen" -->
                            <button class="registerDropdownOption active" type="button" data-value="" hidden>Select a recipient</button>
                            <button class="registerDropdownOption" id="recipientAllOption" type="button" data-value="1">Everyone</button>
                            <button class="registerDropdownOption" id="recipientCoachOption" type="button" data-value="2">Coaches</button>
                            <button class="registerDropdownOption" id="recipientStudentOption" type="button" data-value="3">Students</button>
                            <button class="registerDropdownOption" id="recipientAthleteOption" type="button" data-value="4">Athletes</button>
                            <button class="registerDropdownOption" id="recipientApplicantOption" type="button" data-value="5">Applicants</button>
                        </div>
                        <input id="announcementRecipientInput" name="announcement_recipients_id" type="hidden" value="">
                        <p class="announcement-recipient-error" id="announcementRecipientError" role="alert">Please choose a recipient.</p>
                    </div>
                </div>
                <input id="announcementIdInput" name="announcement_id" type="hidden" value="">
            </div>
            <footer class="announcement-modal-footer" id="announcementModalFooter">
                <button class="btn btn-outline-secondary" id="cancelAnnouncementButton" type="button">Cancel</button>
                <button class="btn btn-primary" id="saveAnnouncementButton" type="submit">Publish</button>
            </footer>
        </form>
    </div>
</div>

<script src="{{ asset('assets/js/fbss-dropdown.js') }}"></script>
<script>
(function () {

    // Endpoints — Laravel routes.
    const LIST_URL = @json(url('/announcements'));
    const SAVE_URL = @json(url('/announcements'));

    // announcement_recipients_id -> label, per the tbl_announcements schema.
    const RECIPIENT_LABELS = { 1: 'Everyone', 2: 'Coaches', 3: 'Students', 4: 'Athletes', 5: 'Applicants' };
    const FILTERS = [
        { id: 'all', label: 'All' },
        { id: 1, label: 'Everyone' },
        { id: 2, label: 'Coaches' },
        { id: 3, label: 'Students' },
        { id: 4, label: 'Athletes' },
        { id: 5, label: 'Applicants' }
    ];

    function formatDate(mysqlDatetime) {
        if (!mysqlDatetime) return '';
        const parsed = new Date(mysqlDatetime.replace(' ', 'T'));
        if (isNaN(parsed.getTime())) return mysqlDatetime;
        return parsed.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
    }

    // Normalizes a raw DB row (snake_case, as returned by the endpoints) into
    // what the card renderer expects.
    function mapRow(row) {
        return {
            id: Number(row.announcement_id),
            title: row.announcement_title,
            message: row.announcement_message,
            postedAt: row.announcement_posted_at,
            updatedAt: row.announcement_updated_at,
            recipientId: Number(row.announcement_recipients_id)
        };
    }

    // Thin wrapper so a missing global toast script degrades to console
    // logging instead of throwing.
    function notify(message, type) {
        if (typeof showToast === 'function') {
            showToast(message, type);
        } else {
            console.log('[' + type + ']', message);
        }
    }

    let announcements = [];
    let activeFilter = 'all';
    let isLoading = true;
    let loadError = '';

    // DOM references: static elements used by the reusable cards and the two modals.
    const filterBar = document.getElementById('announcementsFilterBar');
    const list = document.getElementById('announcementsList');

    const modal = document.getElementById('announcementModal');
    const form = document.getElementById('announcementForm');
    const modalTitle = document.getElementById('announcementModalTitle');
    const saveButton = document.getElementById('saveAnnouncementButton');
    const idInput = document.getElementById('announcementIdInput');
    const recipientInput = document.getElementById('announcementRecipientInput');
    const recipientToggle = document.getElementById('announcementRecipientToggle');
    const recipientDropdown = document.getElementById('announcementRecipientDropdown');
    const recipientError = document.getElementById('announcementRecipientError');

    const viewModal = document.getElementById('announcementViewModal');
    const viewBadge = document.getElementById('announcementViewBadge');
    const viewTitle = document.getElementById('announcementViewTitle');
    const viewPosted = document.getElementById('announcementViewPosted');
    const viewUpdated = document.getElementById('announcementViewUpdated');
    const viewMessage = document.getElementById('announcementViewMessage');
    const viewEditButton = document.getElementById('viewEditButton');
    const viewDeleteButton = document.getElementById('viewDeleteButton');
    let viewingAnnouncement = null;

    // Loads (or reloads) the full list from the index route.
    async function loadAnnouncements() {
        isLoading = true;
        loadError = '';
        renderAnnouncements();
        try {
            const res = await fetch(LIST_URL, { headers: { 'Accept': 'application/json' } });
            const data = await res.json();
            if (!data.success) throw new Error(data.message || 'Failed to load announcements.');
            announcements = (data.announcements || []).map(mapRow);
        } catch (err) {
            announcements = [];
            loadError = err.message || 'Failed to load announcements.';
        } finally {
            isLoading = false;
            renderAnnouncements();
        }
    }

    // Filter bar: one tab per recipient audience, plus "All". Counts reflect current data.
    function renderFilterBar() {
        filterBar.replaceChildren();
        FILTERS.forEach(function (filter) {
            const count = filter.id === 'all'
                ? announcements.length
                : announcements.filter(function (a) { return matchesFilter(a, filter.id); }).length;
            const isSelected = activeFilter === filter.id;

            const tab = document.createElement('button');
            tab.type = 'button';
            tab.id = 'announcementFilter-' + filter.id;
            tab.className = 'btn btn-outline-primary btn-tab' + (isSelected ? ' active' : '');
            tab.setAttribute('role', 'tab');
            tab.setAttribute('aria-selected', String(isSelected));
            tab.dataset.announcementFilter = filter.id;
            tab.textContent = filter.label;

            const countSpan = document.createElement('span');
            countSpan.className = 'announcements-filter-count';
            countSpan.textContent = '(' + count + ')';
            tab.append(countSpan);

            tab.addEventListener('click', function () { setFilter(filter.id); });
            filterBar.append(tab);
        });
    }

    function setFilter(filterId) {
        activeFilter = filterId;
        renderAnnouncements();
    }

    function matchesFilter(announcement, filterId) {
        if (filterId === 'all') return true;
        return announcement.recipientId === filterId;
    }

    // Reusable component: creates one announcement card. The card itself is
    // clickable and opens the view popup; edit/delete buttons stop that click
    // from bubbling so they can act independently.
    function createAnnouncementCard(announcement) {
        const card = document.createElement('article');
        card.id = 'announcementCard-' + announcement.id;
        card.className = 'announcement-card';
        card.tabIndex = 0;
        card.setAttribute('role', 'button');
        card.setAttribute('aria-label', 'View ' + announcement.title);
        card.addEventListener('click', function () { openViewModal(announcement); });
        card.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                openViewModal(announcement);
            }
        });

        const icon = document.createElement('i');
        icon.id = 'announcementIcon-' + announcement.id;
        icon.className = 'bi bi-megaphone-fill announcement-icon';

        const content = document.createElement('div');
        content.id = 'announcementContent-' + announcement.id;

        const title = document.createElement('h3');
        title.id = 'announcementCardTitle-' + announcement.id;
        title.className = 'announcement-title';
        title.textContent = announcement.title;

        // Truncated preview only; the popup shows the full message.
        const messageWrap = document.createElement('div');
        messageWrap.id = 'announcementMessageWrap-' + announcement.id;
        messageWrap.className = 'announcement-message-wrap';
        const message = document.createElement('p');
        message.id = 'announcementMessage-' + announcement.id;
        message.className = 'announcement-message';
        message.textContent = announcement.message;
        messageWrap.append(message);

        const readMoreButton = document.createElement('button');
        readMoreButton.id = 'announcementReadMore-' + announcement.id;
        readMoreButton.type = 'button';
        readMoreButton.className = 'announcement-read-more';
        readMoreButton.innerHTML = 'Read more <i class="bi bi-arrow-right"></i>';
        readMoreButton.addEventListener('click', function (event) {
            event.stopPropagation();
            openViewModal(announcement);
        });

        const meta = document.createElement('div');
        meta.id = 'announcementMeta-' + announcement.id;
        meta.className = 'announcement-meta';

        const posted = document.createElement('span');
        posted.id = 'announcementPosted-' + announcement.id;
        posted.textContent = 'DATE POSTED: ' + formatDate(announcement.postedAt);

        const updated = document.createElement('span');
        updated.id = 'announcementUpdated-' + announcement.id;
        updated.textContent = 'LAST UPDATED: ' + formatDate(announcement.updatedAt);

        const recipients = document.createElement('span');
        recipients.id = 'announcementRecipients-' + announcement.id;
        recipients.textContent = 'RECIPIENTS: ';
        const recipientName = document.createElement('span');
        recipientName.id = 'announcementRecipient-' + announcement.id;
        recipientName.className = 'announcement-recipient';
        recipientName.textContent = RECIPIENT_LABELS[announcement.recipientId] || 'Unknown';
        recipients.append(recipientName);

        meta.append(posted, updated, recipients);
        content.append(title, messageWrap, readMoreButton, meta);

        const editButton = document.createElement('button');
        editButton.id = 'editAnnouncementButton-' + announcement.id;
        editButton.type = 'button';
        editButton.className = 'btn btn-outline-primary';
        editButton.setAttribute('aria-label', 'Edit ' + announcement.title);
        editButton.innerHTML = '<i class="bi bi-pencil-square"></i>';
        editButton.addEventListener('click', function (event) {
            event.stopPropagation();
            openAnnouncementModal(announcement);
        });

        const deleteButton = document.createElement('button');
        deleteButton.id = 'deleteAnnouncementButton-' + announcement.id;
        deleteButton.type = 'button';
        deleteButton.className = 'btn btn-outline-danger';
        deleteButton.setAttribute('aria-label', 'Delete ' + announcement.title);
        deleteButton.innerHTML = '<i class="bi bi-trash"></i>';
        deleteButton.addEventListener('click', function (event) {
            event.stopPropagation();
            deleteAnnouncement(announcement);
        });

        const actions = document.createElement('div');
        actions.id = 'announcementActions-' + announcement.id;
        actions.className = 'announcement-actions';
        actions.append(editButton, deleteButton);

        card.append(icon, content, actions);
        return card;
    }

    // List renderer: redraws the filter bar and the (filtered) scrollable card list,
    // covering loading / error / empty / populated states explicitly.
    function renderAnnouncements() {
        renderFilterBar();
        list.replaceChildren();

        if (isLoading) {
            const loading = document.createElement('p');
            loading.id = 'announcementsLoadingState';
            loading.className = 'announcements-loading';
            loading.textContent = 'Loading announcements…';
            list.append(loading);
            return;
        }

        if (loadError) {
            const errorBox = document.createElement('div');
            errorBox.id = 'announcementsErrorState';
            errorBox.className = 'announcements-error';
            const errorText = document.createElement('p');
            errorText.className = 'mb-0';
            errorText.textContent = 'Couldn\u2019t load announcements: ' + loadError;
            const retryButton = document.createElement('button');
            retryButton.type = 'button';
            retryButton.className = 'btn btn-outline-primary btn-sm announcements-error-retry';
            retryButton.textContent = 'Retry';
            retryButton.addEventListener('click', loadAnnouncements);
            errorBox.append(errorText, retryButton);
            list.append(errorBox);
            return;
        }

        const visible = announcements.filter(function (a) { return matchesFilter(a, activeFilter); });

        if (!visible.length) {
            const empty = document.createElement('p');
            empty.id = 'announcementsEmptyState';
            empty.className = 'announcements-empty';
            empty.textContent = announcements.length
                ? 'No announcements for this audience yet.'
                : 'No announcements have been published.';
            list.append(empty);
            return;
        }

        visible.forEach(function (announcement) { list.append(createAnnouncementCard(announcement)); });
    }

    // ---- View popup -------------------------------------------------------

    function openViewModal(announcement) {
        viewingAnnouncement = announcement;
        viewBadge.textContent = RECIPIENT_LABELS[announcement.recipientId] || 'Unknown';
        viewTitle.textContent = announcement.title;
        viewPosted.innerHTML = '<i class="bi bi-calendar-event"></i> Posted ' + formatDate(announcement.postedAt);
        viewUpdated.innerHTML = '<i class="bi bi-clock-history"></i> Updated ' + formatDate(announcement.updatedAt);
        viewMessage.textContent = announcement.message;

        viewModal.hidden = false;
        document.getElementById('closeAnnouncementViewButton').focus();
    }

    function closeViewModal() {
        viewModal.hidden = true;
        viewingAnnouncement = null;
    }

    document.getElementById('closeAnnouncementViewButton').addEventListener('click', closeViewModal);
    document.getElementById('closeAnnouncementViewButtonFooter').addEventListener('click', closeViewModal);

    viewEditButton.addEventListener('click', function () {
        const announcement = viewingAnnouncement;
        closeViewModal();
        openAnnouncementModal(announcement);
    });

    viewDeleteButton.addEventListener('click', function () {
        const announcement = viewingAnnouncement;
        deleteAnnouncement(announcement, closeViewModal);
    });

    // ---- Create / edit form -------------------------------------------------

    // Selects a recipient on the shared dropdown (silent); '' = nothing chosen.
    function setRecipient(id) {
        window.FBSSDropdown.set(recipientDropdown, id ? String(id) : '');
        recipientDropdown.classList.toggle('isPlaceholder', !id);
        if (id) clearRecipientError();
    }

    function showRecipientError() {
        recipientError.classList.add('is-visible');
        recipientDropdown.classList.add('isInvalid');
        recipientToggle.setAttribute('aria-invalid', 'true');
    }

    function clearRecipientError() {
        recipientError.classList.remove('is-visible');
        recipientDropdown.classList.remove('isInvalid');
        recipientToggle.removeAttribute('aria-invalid');
    }

    function csrfToken() {
        const input = form.querySelector('input[name="_token"]');
        return input ? input.value : '';
    }

    // Deletes an announcement after confirmation, then removes it from the
    // in-memory list and re-renders. Accepts an optional callback (used to
    // close the view popup on success) run before the toast fires.
    async function deleteAnnouncement(announcement, onSuccess) {
        const confirmed = window.confirm('Delete "' + announcement.title + '"? This cannot be undone.');
        if (!confirmed) return;

        try {
            const res = await fetch(LIST_URL + '/' + announcement.id, {
                method: 'DELETE',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken() }
            });
            const data = await res.json();
            if (!data.success) throw new Error(data.message || 'Failed to delete announcement.');

            announcements = announcements.filter(function (a) { return a.id !== announcement.id; });
            renderAnnouncements();
            if (typeof onSuccess === 'function') onSuccess();
            notify('Announcement deleted.', 'success');
        } catch (err) {
            notify(err.message || 'Failed to delete announcement.', 'danger');
        }
    }

    // Form controller: opens either a blank Publish form or a populated Edit form.
    function openAnnouncementModal(announcement) {
        const token = form.elements._token ? form.elements._token.value : '';
        form.reset();
        if (form.elements._token) form.elements._token.value = token;
        clearRecipientError();
        if (announcement) {
            idInput.value = announcement.id;
            form.elements.announcement_title.value = announcement.title;
            form.elements.announcement_message.value = announcement.message;
            setRecipient(announcement.recipientId);
            modalTitle.textContent = 'Edit Announcement';
            saveButton.textContent = 'Save Changes';
        } else {
            idInput.value = '';
            setRecipient('');
            modalTitle.textContent = 'New Announcement';
            saveButton.textContent = 'Publish';
        }
        modal.hidden = false;
        document.getElementById('announcementTitleInput').focus();
    }

    function closeAnnouncementModal() {
        modal.hidden = true;
    }

    // Event bindings.
    document.getElementById('addAnnouncementButton').addEventListener('click', function () { openAnnouncementModal(); });
    document.getElementById('cancelAnnouncementButton').addEventListener('click', closeAnnouncementModal);
    // Fired by the shared dropdown (assets/js/fbss-dropdown.js) when a recipient is picked.
    recipientDropdown.addEventListener('dropdown:change', function (event) {
        recipientDropdown.classList.toggle('isPlaceholder', !event.detail.value);
        if (event.detail.value) clearRecipientError();
    });
    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') return;
        if (!modal.hidden) closeAnnouncementModal();
        if (!viewModal.hidden) closeViewModal();
    });

    // Publishing/saving: POSTs to the store route (the @csrf field in the form
    // supplies the token), merges the real saved row back into the list, and
    // reports the result via toast instead of an inline banner.
    form.addEventListener('submit', async function (event) {
        event.preventDefault();

        if (!form.checkValidity()) { form.reportValidity(); return; }
        if (!recipientInput.value) {
            showRecipientError();
            recipientToggle.focus();
            return;
        }

        const isEditing = Boolean(idInput.value);
        saveButton.disabled = true;
        saveButton.textContent = isEditing ? 'Saving…' : 'Publishing…';

        try {
            const res = await fetch(SAVE_URL, { method: 'POST', headers: { 'Accept': 'application/json' }, body: new FormData(form) });
            const data = await res.json();
            if (!data.success) throw new Error(data.message || 'Failed to save announcement.');

            const saved = mapRow(data.announcement);
            const index = announcements.findIndex(function (a) { return a.id === saved.id; });
            if (index >= 0) {
                announcements[index] = saved;
            } else {
                announcements.unshift(saved);
            }
            closeAnnouncementModal();
            renderAnnouncements();
            notify(isEditing ? 'Announcement updated.' : 'Announcement published.', 'success');
        } catch (err) {
            notify(err.message || 'Failed to save announcement.', 'danger');
        } finally {
            saveButton.disabled = false;
            saveButton.textContent = isEditing ? 'Save Changes' : 'Publish';
        }
    });

    loadAnnouncements();
}());
</script>