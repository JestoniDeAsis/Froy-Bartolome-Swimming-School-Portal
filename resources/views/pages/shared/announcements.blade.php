<style>
.announcements-container {
  width: 100%;
  padding: 1.25rem;
}

.announcements-header {
  margin-bottom: 1rem;
}

.announcements-filter-bar {
  margin-bottom: 0.75rem;
}

.announcements-filter-count {
  margin-left: 0.35rem;
  opacity: 0.75;
}

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
  grid-template-columns: auto 1fr;
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
  gap: 0.25rem 1rem;
  margin-top: 0.75rem;
  color: #6c757d;
  font-size: 0.75rem;
  font-weight: 500;
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

/* View popup, same shell as the admin's — but read-only (no edit/delete). */
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
  width: min(560px, 100%);
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

.announcement-modal-body {
  padding: 1.25rem;
}

.announcement-view-message {
  margin: 0;
  color: #343A40;
  font-size: 0.95rem;
  line-height: 1.65;
  white-space: pre-wrap;
  word-break: break-word;
}

.announcement-modal-footer {
  display: flex;
  justify-content: flex-end;
  border-top: 1px solid #E7EBEF;
}

@media (max-width: 575.98px) {
  .announcements-container {
    padding: 1rem;
  }

  .announcements-scroll {
    max-height: 420px;
  }
}
</style>

<!-- Read-only announcement feed for regular (non-admin) users. -->
<section class="announcements-container" id="announcementsContainer" aria-labelledby="announcementsTitle">
    <header class="announcements-header" id="announcementsHeader">
        <h2 class="mb-1" id="announcementsTitle">Announcements</h2>
        <p class="text-muted mb-0" id="announcementsDescription">Messages from the school.</p>
    </header>

    <!-- Only two tabs: everything relevant to this viewer, and just their own audience. -->
    <nav class="announcements-filter-bar btn-tab-group" id="announcementsFilterBar" role="tablist" aria-label="Filter announcements"></nav>

    <div class="announcements-scroll" id="announcementsScroll">
        <div class="announcements-list" id="announcementsList"></div>
    </div>
</section>

<!-- View popup: full announcement text, read-only. -->
<div class="announcement-modal" id="announcementViewModal" aria-hidden="true">
    <div class="announcement-modal-dialog" id="announcementViewDialog" role="dialog" aria-modal="true" aria-labelledby="announcementViewTitle">
        <header class="announcement-modal-header" id="announcementViewHeader">
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
        <footer class="announcement-modal-footer" id="announcementViewFooter">
            <button class="btn btn-primary" id="closeAnnouncementViewButtonFooter" type="button">Close</button>
        </footer>
    </div>
</div>

<script>
(function () {

    const FEED_URL = @json(url('/announcements/feed'));

    const RECIPIENT_LABELS = { 1: 'Everyone', 2: 'Coaches', 3: 'Students', 4: 'Athletes', 5: 'Applicants' };

    function formatDate(mysqlDatetime) {
        if (!mysqlDatetime) return '';
        const parsed = new Date(mysqlDatetime.replace(' ', 'T'));
        if (isNaN(parsed.getTime())) return mysqlDatetime;
        return parsed.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
    }

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

    function notify(message, type) {
        if (typeof showToast === 'function') {
            showToast(message, type);
        } else {
            console.log('[' + type + ']', message);
        }
    }

    let announcements = [];
    // The server tells us which specific role this viewer belongs to (2/3/4/5),
    // so the second tab always matches their actual audience.
    let viewerRecipientId = null;
    let activeFilter = 'all';
    let isLoading = true;
    let loadError = '';

    const filterBar = document.getElementById('announcementsFilterBar');
    const list = document.getElementById('announcementsList');

    const viewModal = document.getElementById('announcementViewModal');
    const viewBadge = document.getElementById('announcementViewBadge');
    const viewTitle = document.getElementById('announcementViewTitle');
    const viewPosted = document.getElementById('announcementViewPosted');
    const viewUpdated = document.getElementById('announcementViewUpdated');
    const viewMessage = document.getElementById('announcementViewMessage');

    async function loadAnnouncements() {
        isLoading = true;
        loadError = '';
        render();
        try {
            const res = await fetch(FEED_URL);
            const data = await res.json();
            if (!data.success) throw new Error(data.message || 'Failed to load announcements.');
            announcements = (data.announcements || []).map(mapRow);
            viewerRecipientId = Number(data.viewer_recipient_id) || null;
        } catch (err) {
            announcements = [];
            loadError = err.message || 'Failed to load announcements.';
            notify(loadError, 'danger');
        } finally {
            isLoading = false;
            render();
        }
    }

    // "All" = everything this viewer can see. "Everyone" = broadcast-only.
    // Third tab ("For you": announcements addressed to the viewer's own role —
    // Coaches/Students/Athletes/Applicants) only appears for roles that have
    // one — admins viewing this page would just see All + Everyone.
    function getFilters() {
        const filters = [
            { id: 'all', label: 'All' },
            { id: 1, label: 'Everyone' }
        ];
        if (viewerRecipientId && viewerRecipientId !== 1) {
            filters.push({ id: viewerRecipientId, label: 'For you' });
        }
        return filters;
    }

    function matchesFilter(announcement, filterId) {
        if (filterId === 'all') return true;
        return announcement.recipientId === filterId;
    }

    function renderFilterBar() {
        const filters = getFilters();
        filterBar.replaceChildren();
        filters.forEach(function (filter) {
            const count = announcements.filter(function (a) { return matchesFilter(a, filter.id); }).length;
            const isSelected = activeFilter === filter.id;

            const tab = document.createElement('button');
            tab.type = 'button';
            tab.className = 'btn btn-outline-primary btn-tab' + (isSelected ? ' active' : '');
            tab.setAttribute('role', 'tab');
            tab.setAttribute('aria-selected', String(isSelected));
            tab.textContent = filter.label;

            const countSpan = document.createElement('span');
            countSpan.className = 'announcements-filter-count';
            countSpan.textContent = '(' + count + ')';
            tab.append(countSpan);

            tab.addEventListener('click', function () {
                activeFilter = filter.id;
                render();
            });
            filterBar.append(tab);
        });
    }

    function createAnnouncementCard(announcement) {
        const card = document.createElement('article');
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
        icon.className = 'bi bi-megaphone-fill announcement-icon';

        const content = document.createElement('div');

        const title = document.createElement('h3');
        title.className = 'announcement-title';
        title.textContent = announcement.title;

        const messageWrap = document.createElement('div');
        messageWrap.className = 'announcement-message-wrap';
        const message = document.createElement('p');
        message.className = 'announcement-message';
        message.textContent = announcement.message;
        messageWrap.append(message);

        const readMoreButton = document.createElement('button');
        readMoreButton.type = 'button';
        readMoreButton.className = 'announcement-read-more';
        readMoreButton.innerHTML = 'Read more <i class="bi bi-arrow-right"></i>';
        readMoreButton.addEventListener('click', function (event) {
            event.stopPropagation();
            openViewModal(announcement);
        });

        const meta = document.createElement('div');
        meta.className = 'announcement-meta';

        const posted = document.createElement('span');
        posted.textContent = 'DATE POSTED: ' + formatDate(announcement.postedAt);

        const recipients = document.createElement('span');
        recipients.textContent = 'FOR: ';
        const recipientName = document.createElement('span');
        recipientName.className = 'announcement-recipient';
        recipientName.textContent = RECIPIENT_LABELS[announcement.recipientId] || 'Unknown';
        recipients.append(recipientName);

        meta.append(posted, recipients);
        content.append(title, messageWrap, readMoreButton, meta);
        card.append(icon, content);
        return card;
    }

    function render() {
        renderFilterBar();
        list.replaceChildren();

        if (isLoading) {
            const loading = document.createElement('p');
            loading.className = 'announcements-loading';
            loading.textContent = 'Loading announcements…';
            list.append(loading);
            return;
        }

        if (loadError) {
            const errorBox = document.createElement('div');
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
            empty.className = 'announcements-empty';
            empty.textContent = 'No announcements yet.';
            list.append(empty);
            return;
        }

        visible.forEach(function (announcement) { list.append(createAnnouncementCard(announcement)); });
    }

    function openViewModal(announcement) {
        viewBadge.textContent = RECIPIENT_LABELS[announcement.recipientId] || 'Unknown';
        viewTitle.textContent = announcement.title;
        viewPosted.innerHTML = '<i class="bi bi-calendar-event"></i> Posted ' + formatDate(announcement.postedAt);
        viewUpdated.innerHTML = '<i class="bi bi-clock-history"></i> Updated ' + formatDate(announcement.updatedAt);
        viewMessage.textContent = announcement.message;

        viewModal.classList.add('is-open');
        viewModal.setAttribute('aria-hidden', 'false');
        document.getElementById('closeAnnouncementViewButton').focus();
    }

    function closeViewModal() {
        viewModal.classList.remove('is-open');
        viewModal.setAttribute('aria-hidden', 'true');
    }

    document.getElementById('closeAnnouncementViewButton').addEventListener('click', closeViewModal);
    document.getElementById('closeAnnouncementViewButtonFooter').addEventListener('click', closeViewModal);
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && viewModal.classList.contains('is-open')) closeViewModal();
    });

    loadAnnouncements();
}());
</script>