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

/* Filter bar: lets admins narrow the scrollable list to one audience. */
.announcements-filter-bar {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-bottom: 0.75rem;
}

.announcements-filter-pill {
  padding: 0.4rem 0.85rem;
  border: 1px solid #D1D5DB;
  border-radius: 999px;
  background: #ffffff;
  color: #495057;
  font-size: 0.8125rem;
  font-weight: 500;
  line-height: 1.4;
}

.announcements-filter-pill:hover {
  border-color: #0093E5;
  color: #045199;
}

.announcements-filter-pill:focus-visible {
  outline: 2px solid #0093E5;
  outline-offset: 2px;
}

.announcements-filter-pill.is-active {
  background: #0093E5;
  border-color: #0093E5;
  color: #ffffff;
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

.announcement-card {
  display: grid;
  grid-template-columns: auto 1fr auto;
  gap: 0.75rem;
  padding: 1rem;
  background: #ffffff;
  border: 1px solid #E7EBEF;
  border-radius: 10px;
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

/* Collapsed by default; JS toggles .is-expanded to lift the max-height. */
.announcement-message-wrap {
  position: relative;
  max-height: 4.2rem;
  overflow: hidden;
}

.announcement-message-wrap.is-expanded {
  max-height: none;
}

.announcement-message-wrap:not(.is-expanded)::after {
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

.announcement-toggle-button {
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

.announcement-toggle-button:hover,
.announcement-toggle-button:focus-visible {
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

.announcement-edit-button {
  align-self: start;
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

/* Shared modal used for creating and editing announcements. */
.announcement-modal {
  position: fixed;
  inset: 0;
  z-index: 1050;
  display: none;
  align-items: center;
  justify-content: center;
  padding: 1.25rem;
  background: rgba(31, 41, 55, 0.45);
}

.announcement-modal.is-open {
  display: flex;
}

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

.announcement-form-error {
  display: none;
  margin: 0 auto 0 0;
  color: #E03131;
  font-size: 0.8125rem;
}

.announcement-form-error.is-visible {
  display: block;
}

.announcement-required-mark {
  color: #E03131;
  margin-left: 0.15rem;
}

/* Custom recipient dropdown: enables a styled choices panel instead of a native select popup. */
.announcement-recipient-dropdown {
  position: relative;
  margin-bottom: 0.25rem;
}

.announcement-recipient-toggle {
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
  padding: 10px 14px;
  border: 1px solid #D1D5DB;
  border-radius: 10px;
  background: #ffffff;
  color: #212529;
  text-align: left;
}

.announcement-recipient-toggle.is-placeholder {
  color: #6c757d;
}

.announcement-recipient-toggle:focus {
  border-color: #0093E5;
  box-shadow: 0 0 0 .2rem rgba(0, 147, 229, .15);
  outline: 0;
}

.announcement-recipient-toggle.is-invalid {
  border-color: #E03131;
}

.announcement-recipient-toggle.is-invalid:focus {
  box-shadow: 0 0 0 .2rem rgba(224, 49, 49, .15);
}

/* Keeps long recipient lists scrollable inside the choices container. */
.announcement-recipient-options {
  position: static;
  margin-top: 0.5rem;
  display: grid;
  gap: 0.25rem;
  max-height: 180px;
  overflow-y: auto;
  padding: 0.5rem;
  background: #ffffff;
  border: 1px solid #E7EBEF;
  border-radius: 10px;
  box-shadow: 0 8px 18px rgba(20, 30, 50, 0.12);
}

.announcement-recipient-option {
  width: 100%;
  padding: 0.55rem 0.75rem;
  border: 0;
  border-radius: 7px;
  background: transparent;
  color: #212529;
  text-align: left;
}

.announcement-recipient-option:hover,
.announcement-recipient-option:focus {
  background: #e7f5ff;
  color: #045199;
  outline: 0;
}

.announcement-recipient-error {
  display: none;
  margin: 0.4rem 0 0;
  color: #E03131;
  font-size: 0.8125rem;
}

.announcement-recipient-error.is-visible {
  display: block;
}

@media (max-width: 575.98px) {
  .announcements-container {
    padding: 1rem;
  }

  .announcements-header {
    flex-direction: column;
  }

  #addAnnouncementButton {
    width: 100%;
  }

  .announcements-scroll {
    max-height: 420px;
  }

  .announcement-card {
    grid-template-columns: auto 1fr;
  }

  .announcement-edit-button {
    grid-column: 2;
    justify-self: end;
  }

  .announcement-modal-footer {
    flex-wrap: wrap;
  }

  .announcement-form-error {
    width: 100%;
    margin: 0 0 0.5rem;
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
        <button class="btn btn-outline-primary" id="addAnnouncementButton" type="button">
            <i class="bi bi-plus-lg" id="addAnnouncementIcon"></i>
            New Announcement
        </button>
    </header>

    <!-- Filter pills; renderAnnouncements() applies the active filter and updates counts. -->
    <div class="announcements-filter-bar" id="announcementsFilterBar" role="group" aria-label="Filter announcements by recipient"></div>

    <!-- Reusable announcement cards are rendered into announcementsList by renderAnnouncements(). -->
    <div class="announcements-scroll" id="announcementsScroll">
        <div class="announcements-list" id="announcementsList"></div>
    </div>
</section>

<!-- One modal reused for both new announcements and edits. -->
<div class="announcement-modal" id="announcementModal" aria-hidden="true">
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
                    <!-- Styled dropdown; the hidden field supplies announcement_recipients_id to the form. -->
                    <div class="announcement-recipient-dropdown" id="announcementRecipientDropdown">
                        <button class="announcement-recipient-toggle is-placeholder" id="announcementRecipientToggle" type="button" aria-expanded="false" aria-controls="announcementRecipientOptions" aria-required="true">
                            <span id="announcementRecipientSelected">Select a recipient</span>
                            <i class="bi bi-chevron-down" id="announcementRecipientChevron"></i>
                        </button>
                        <div class="announcement-recipient-options" id="announcementRecipientOptions" hidden>
                            <button class="announcement-recipient-option" id="recipientAllOption" type="button" data-recipient-id="1">Everyone</button>
                            <button class="announcement-recipient-option" id="recipientCoachOption" type="button" data-recipient-id="2">Coaches</button>
                            <button class="announcement-recipient-option" id="recipientStudentOption" type="button" data-recipient-id="3">Students</button>
                            <button class="announcement-recipient-option" id="recipientAthleteOption" type="button" data-recipient-id="4">Athletes</button>
                        </div>
                        <input id="announcementRecipientInput" name="announcement_recipients_id" type="hidden" value="">
                        <p class="announcement-recipient-error" id="announcementRecipientError" role="alert">Please choose a recipient.</p>
                    </div>
                </div>
                <input id="announcementIdInput" name="announcement_id" type="hidden" value="">
            </div>
            <footer class="announcement-modal-footer" id="announcementModalFooter">
                <p class="announcement-form-error" id="announcementFormError" role="alert"></p>
                <button class="btn btn-outline-secondary" id="cancelAnnouncementButton" type="button">Cancel</button>
                <button class="btn btn-primary" id="saveAnnouncementButton" type="submit">Publish</button>
            </footer>
        </form>
    </div>
</div>

<script>
(function () {

    // Endpoints — now Laravel routes instead of hardcoded file paths.
    const LIST_URL = @json(url('/announcements'));
    const SAVE_URL = @json(url('/announcements'));

    // announcement_recipients_id -> label, per the tbl_announcements schema.
    const RECIPIENT_LABELS = { 1: 'Everyone', 2: 'Coaches', 3: 'Students', 4: 'Athletes' };
    const FILTERS = [
        { id: 'all', label: 'All' },
        { id: 1, label: 'Everyone' },
        { id: 2, label: 'Coaches' },
        { id: 3, label: 'Students' },
        { id: 4, label: 'Athletes' }
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

    let announcements = [];
    let activeFilter = 'all';
    let isLoading = true;
    let loadError = '';

    // DOM references: static elements used by the reusable cards and add/edit modal.
    const filterBar = document.getElementById('announcementsFilterBar');
    const list = document.getElementById('announcementsList');
    const modal = document.getElementById('announcementModal');
    const form = document.getElementById('announcementForm');
    const modalTitle = document.getElementById('announcementModalTitle');
    const saveButton = document.getElementById('saveAnnouncementButton');
    const idInput = document.getElementById('announcementIdInput');
    const recipientInput = document.getElementById('announcementRecipientInput');
    const recipientToggle = document.getElementById('announcementRecipientToggle');
    const recipientSelected = document.getElementById('announcementRecipientSelected');
    const recipientOptions = document.getElementById('announcementRecipientOptions');
    const recipientError = document.getElementById('announcementRecipientError');
    const formError = document.getElementById('announcementFormError');

    // Loads (or reloads) the full list from the index route.
    async function loadAnnouncements() {
        isLoading = true;
        loadError = '';
        renderAnnouncements();
        try {
            const res = await fetch(LIST_URL);
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

    // Filter bar: one pill per recipient audience, plus "All". Counts reflect current data.
    function renderFilterBar() {
        filterBar.replaceChildren();
        FILTERS.forEach(function (filter) {
            const count = filter.id === 'all'
                ? announcements.length
                : announcements.filter(function (a) { return matchesFilter(a, filter.id); }).length;

            const pill = document.createElement('button');
            pill.type = 'button';
            pill.id = 'announcementFilter-' + filter.id;
            pill.className = 'announcements-filter-pill' + (activeFilter === filter.id ? ' is-active' : '');
            pill.setAttribute('aria-pressed', String(activeFilter === filter.id));
            pill.textContent = filter.label;

            const countSpan = document.createElement('span');
            countSpan.className = 'announcements-filter-count';
            countSpan.textContent = '(' + count + ')';
            pill.append(countSpan);

            pill.addEventListener('click', function () {
                activeFilter = filter.id;
                renderAnnouncements();
            });
            filterBar.append(pill);
        });
    }


    // "All" shows every announcement.
    // Each specific audience filter shows only announcements assigned to that recipient.
    function matchesFilter(announcement, filterId) {
      if (filterId === 'all') return true;
      return announcement.recipientId === filterId;
  }

    // Reusable component: creates one announcement card from a mapped announcement object.
    function createAnnouncementCard(announcement) {
        const card = document.createElement('article');
        card.id = 'announcementCard-' + announcement.id;
        card.className = 'announcement-card';

        const icon = document.createElement('i');
        icon.id = 'announcementIcon-' + announcement.id;
        icon.className = 'bi bi-megaphone-fill announcement-icon';

        const content = document.createElement('div');
        content.id = 'announcementContent-' + announcement.id;

        const title = document.createElement('h3');
        title.id = 'announcementCardTitle-' + announcement.id;
        title.className = 'announcement-title';
        title.textContent = announcement.title;

        // Collapsed by default; expand button below toggles .is-expanded.
        const messageWrap = document.createElement('div');
        messageWrap.id = 'announcementMessageWrap-' + announcement.id;
        messageWrap.className = 'announcement-message-wrap';
        const message = document.createElement('p');
        message.id = 'announcementMessage-' + announcement.id;
        message.className = 'announcement-message';
        message.textContent = announcement.message;
        messageWrap.append(message);

        const toggleButton = document.createElement('button');
        toggleButton.id = 'announcementToggleButton-' + announcement.id;
        toggleButton.type = 'button';
        toggleButton.className = 'announcement-toggle-button';
        toggleButton.setAttribute('aria-expanded', 'false');
        toggleButton.innerHTML = 'Show more <i class="bi bi-chevron-down"></i>';
        toggleButton.addEventListener('click', function () {
            const expanded = messageWrap.classList.toggle('is-expanded');
            toggleButton.setAttribute('aria-expanded', String(expanded));
            toggleButton.innerHTML = expanded
                ? 'Show less <i class="bi bi-chevron-up"></i>'
                : 'Show more <i class="bi bi-chevron-down"></i>';
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
        content.append(title, messageWrap, toggleButton, meta);

        const editButton = document.createElement('button');
        editButton.id = 'editAnnouncementButton-' + announcement.id;
        editButton.type = 'button';
        editButton.className = 'btn btn-outline-primary announcement-edit-button';
        editButton.setAttribute('aria-label', 'Edit ' + announcement.title);
        editButton.innerHTML = '<i class="bi bi-pencil-square"></i>';
        editButton.addEventListener('click', function () { openAnnouncementModal(announcement); });

        card.append(icon, content, editButton);
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

    // Updates the hidden form field and visible label used by the custom recipient dropdown.
    function setRecipient(id, label) {
        recipientInput.value = id || '';
        recipientSelected.textContent = label || 'Select a recipient';
        recipientToggle.classList.toggle('is-placeholder', !id);
        recipientOptions.hidden = true;
        recipientToggle.setAttribute('aria-expanded', 'false');
        if (id) clearRecipientError();
    }

    function showRecipientError() {
        recipientError.classList.add('is-visible');
        recipientToggle.classList.add('is-invalid');
        recipientToggle.setAttribute('aria-invalid', 'true');
    }

    function clearRecipientError() {
        recipientError.classList.remove('is-visible');
        recipientToggle.classList.remove('is-invalid');
        recipientToggle.removeAttribute('aria-invalid');
    }

    function setFormError(message) {
        formError.textContent = message || '';
        formError.classList.toggle('is-visible', Boolean(message));
    }

    // Form controller: opens either a blank Publish form or a populated Edit form.
    function openAnnouncementModal(announcement) {
        const token = form.elements._token ? form.elements._token.value : '';
        form.reset();
        if (form.elements._token) form.elements._token.value = token;
        clearRecipientError();
        setFormError('');
        if (announcement) {
            idInput.value = announcement.id;
            form.elements.announcement_title.value = announcement.title;
            form.elements.announcement_message.value = announcement.message;
            setRecipient(announcement.recipientId, RECIPIENT_LABELS[announcement.recipientId]);
            modalTitle.textContent = 'Edit Announcement';
            saveButton.textContent = 'Save Changes';
        } else {
            idInput.value = '';
            setRecipient('', '');
            modalTitle.textContent = 'New Announcement';
            saveButton.textContent = 'Publish';
        }
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.getElementById('announcementTitleInput').focus();
    }

    // Closes the modal.
    function closeAnnouncementModal() {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
    }

    // Event bindings.
    document.getElementById('addAnnouncementButton').addEventListener('click', function () { openAnnouncementModal(); });
    document.getElementById('cancelAnnouncementButton').addEventListener('click', closeAnnouncementModal);
    recipientToggle.addEventListener('click', function () {
        const isOpen = !recipientOptions.hidden;
        recipientOptions.hidden = isOpen;
        recipientToggle.setAttribute('aria-expanded', String(!isOpen));
    });
    recipientOptions.addEventListener('click', function (event) {
        const option = event.target.closest('[data-recipient-id]');
        if (option) {
            const id = Number(option.dataset.recipientId);
            setRecipient(id, RECIPIENT_LABELS[id]);
        }
    });
    modal.addEventListener('click', function (event) { if (event.target === modal) closeAnnouncementModal(); });
    document.addEventListener('click', function (event) {
        if (!event.target.closest('#announcementRecipientDropdown')) {
            recipientOptions.hidden = true;
            recipientToggle.setAttribute('aria-expanded', 'false');
        }
    });
    document.addEventListener('keydown', function (event) { if (event.key === 'Escape' && modal.classList.contains('is-open')) closeAnnouncementModal(); });

    // Publishing/saving: POSTs to the store route (the @csrf field in the form
    // supplies the token) and merges the real saved row back into the list.
    form.addEventListener('submit', async function (event) {
        event.preventDefault();
        setFormError('');

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
            const res = await fetch(SAVE_URL, { method: 'POST', body: new FormData(form) });
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
        } catch (err) {
            setFormError(err.message || 'Failed to save announcement.');
        } finally {
            saveButton.disabled = false;
            saveButton.textContent = isEditing ? 'Save Changes' : 'Publish';
        }
    });

    loadAnnouncements();
}());
</script>