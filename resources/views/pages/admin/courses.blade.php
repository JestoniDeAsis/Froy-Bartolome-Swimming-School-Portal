@extends('layouts.toast')

@php
    // Self-contained, like the original PHP file: fetches its own listing
    // so it can be rendered by name from role_menus.php without needing a
    // dedicated controller/route just to display it.
    $courses = \DB::table('tbl_courses as co')
        ->leftJoin('tbl_course_categories as c', function ($join) {
            $join->on('c.course_id', '=', 'co.course_id')->whereNull('c.deleted_at');
        })
        ->leftJoin('tbl_course_skills as s', function ($join) {
            $join->on('s.course_category_id', '=', 'c.course_category_id')->whereNull('s.deleted_at');
        })
        ->select(
            'co.course_id',
            'co.course_name',
            'co.course_session_count',
            'co.course_price',
            \DB::raw('COUNT(s.course_skill_id) as skill_count')
        )
        ->groupBy('co.course_id', 'co.course_name', 'co.course_session_count', 'co.course_price')
        ->orderBy('co.course_name')
        ->get();

    // Counts per course. Same rules as the Graduation page, so the numbers match its tabs.
    $enrolledByCourse = \DB::table('tbl_enrollments as e')
        ->join(\App\Http\Controllers\CoachController::USERS_TABLE . ' as u', 'u.' . \App\Http\Controllers\CoachController::USER_PK, '=', 'e.user_id')
        ->join('tbl_user_profile as p', 'p.user_id', '=', 'e.user_id')
        ->join(\App\Http\Controllers\CoachController::STATUS_TABLE . ' as st', 'st.' . \App\Http\Controllers\CoachController::STATUS_ID_COL, '=', 'u.' . \App\Http\Controllers\CoachController::STATUS_COL)
        ->whereNull('e.graduation_at')
        ->where('st.' . \App\Http\Controllers\CoachController::STATUS_NAME_COL, 'Active')
        ->groupBy('e.course_id')
        ->selectRaw('e.course_id as cid, COUNT(*) as total')
        ->pluck('total', 'cid');

    $graduatesByCourse = \DB::table('tbl_enrollments as e')
        ->join('tbl_user_profile as p', 'p.user_id', '=', 'e.user_id')
        ->whereNotNull('e.graduation_at')
        ->groupBy('e.course_id')
        ->selectRaw('e.course_id as cid, COUNT(*) as total')
        ->pluck('total', 'cid');
@endphp

<style>
/* =========================================
   COURSES CONTAINER
========================================= */

.courses-container {
    width: 100%;
    padding: 1.25rem;
    background-color: transparent;
}

/* =========================================
   COURSES HEADER CONTAINER
========================================= */

.courses-header-container {
    width: 100%;
    margin-bottom: 0.75rem;
}

/* =========================================
   COURSES HEADER
========================================= */

.courses-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 0.25rem;
}

/* =========================================
   COURSES HEADER DESCRIPTION
========================================= */

.courses-header .text-muted {
    margin-bottom: 1.5rem;
}

/* =========================================
   TABLE CONTAINER
========================================= */

.courses-table-container {
    width: 100%;
    background-color: #ffffff;
    border: 1px solid #E7EBEF;
    border-radius: 12px;
    overflow: hidden;
}

/* =========================================
   TABLE TOOLBAR
========================================= */

.courses-table-toolbar {
    display: flex;
    justify-content: flex-start;
    align-items: center;
    padding: 0.75rem 1rem;
}

/* =========================================
   SEARCH CONTAINER
========================================= */

.course-search-container {
    position: relative;
    width: 300px;
}

/* =========================================
   SEARCH ICON
========================================= */

.course-search-container i {
    position: absolute;
    left: 0.7rem;
    top: 50%;
    transform: translateY(-50%);
    color: #6c757d;
    pointer-events: none;
}

/* =========================================
   SEARCH INPUT
========================================= */

#courseSearch {
    width: 100%;
    padding: 10px 14px 10px 2rem;
    border: 1px solid #D1D5DB;
    border-radius: 20px;
    background-color: #ffffff;
    outline: none;
}

#courseSearch:focus {
    border-color: #0093E5;
    box-shadow: 0 0 0 .2rem rgba(0,147,229,.15);
}

#courseSearch::placeholder {
    color: #9aa0a6;
}

/* =========================================
   TABLE WRAPPER
========================================= */

.courses-table-wrapper {
    width: 100%;
    overflow-x: auto;
}

/* =========================================
   COURSES TABLE
========================================= */

#coursesTable {
    width: 100%;
    min-width: 640px;
    border-collapse: collapse;
}

/* =========================================
   TABLE HEADER
========================================= */

#coursesTable thead {
    background-color: #F8FAFB;
}

#coursesTable th {
    padding: 0.65rem 0.75rem;
    border-top: 1px solid #EDF0F2;
    border-bottom: 1px solid #E9EDF0;
    color: #667085;
    font-weight: 600;
    text-align: center;
    white-space: nowrap;
}

/* =========================================
   TABLE BODY
========================================= */

#coursesTable td {
    padding: 0.7rem 0.75rem;
    border-bottom: 1px solid #EDF0F2;
    color: #343A40;
    text-align: center;
    white-space: nowrap;
}

#coursesTable tbody tr:last-child td {
    border-bottom: none;
}

#coursesTable tbody tr:hover {
    background-color: #FAFCFD;
}

/* =========================================
   COURSE NAME
========================================= */

.course-name {
    font-weight: 500;
    color: #343A40;
}

/* =========================================
   FIRST COLUMN
========================================= */

#coursesTable th:first-child,
#coursesTable td:first-child {
    text-align: left;
    padding-left: 1.75rem;
}

/* =========================================
   LAST COLUMN
========================================= */

#coursesTable th:last-child,
#coursesTable td:last-child {
    width: 170px;
}

/* =========================================
   RESPONSIVE
========================================= */

@media (max-width: 767.98px) {
    .courses-container {
        padding: 1rem;
    }
    .courses-header {
        align-items: flex-start;
    }
    .course-search-container {
        width: 160px;
    }
}

@media (max-width: 575.98px) {
    .courses-header {
        flex-direction: column;
        align-items: stretch;
    }
    #addCourseButton {
        width: 100%;
    }
    .courses-table-toolbar {
        justify-content: stretch;
    }
    .course-search-container {
        width: 100%;
    }
}

/* Add course dialog (backdrop = shared .modalOverlay in our_theme.css) */
.course-modal-dialog {
    width: min(940px, 100%);
    max-height: calc(100vh - 2.5rem);
    overflow: auto;
    background: #ffffff;
    border: 1px solid #E7EBEF;
    border-radius: 14px;
    box-shadow: 0 12px 32px rgba(20, 30, 50, 0.16);
}

.course-modal-header,
.course-modal-footer {
    padding: 1rem 1.25rem;
    border-bottom: 1px solid #E7EBEF;
}

.course-modal-header h2 { font-size: 1.1rem; }
.course-modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
    border-top: 1px solid #E7EBEF;
    border-bottom: 0;
}

.course-builder {
    display: grid;
    grid-template-columns: 260px 260px minmax(0, 1fr);
    gap: 1rem;
    padding: 1.25rem;
}

.course-builder-panel {
    display: flex;
    flex-direction: column;
    min-width: 0;
    min-height: 360px;
    border: 1px solid #E7EBEF;
    border-radius: 10px;
    background: #ffffff;
    overflow: hidden;
}

.course-builder-panel-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
    padding: 0.85rem 1rem;
    border-bottom: 1px solid #E7EBEF;
    font-size: 0.875rem;
    font-weight: 700;
}

.course-details { padding: 1rem; }
.course-details .form-label { font-size: 0.8rem; font-weight: 600; }
.course-category-list,
.course-skill-list { flex: 1; overflow-y: auto; padding: 0.4rem; }

.course-list-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.4rem;
    padding: 0.55rem 0.6rem;
    border-radius: 8px;
    cursor: pointer;
}

.course-list-row:hover,
.course-list-row.is-selected { background: #e7f5ff; }
.course-list-row.is-selected { color: #045199; }
.course-list-row-name { min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.course-list-row-count { color: #6c757d; font-size: 0.75rem; }
.course-list-row-actions { display: flex; gap: 0.2rem; }

/* Archived (soft-deleted) categories/skills — kept in the list, visually muted */
.course-list-row.is-archived { color: #9aa0a6; background: #F5F6F7; }
.course-list-row.is-archived:hover { background: #EFF1F2; }
.course-list-row.is-archived.is-selected { background: #EFF1F2; color: #9aa0a6; }
.course-list-row.is-archived .course-list-row-count { color: #b0b5ba; }
.course-archived-badge { font-size: 0.75rem; color: #9aa0a6; margin-right: 0.2rem; }
.course-icon-button { padding: 0.15rem 0.4rem; line-height: 1.2; }
.course-list-row-editing { cursor: default; gap: 0.4rem; }
.course-list-row-edit-input { flex: 1; min-width: 0; }

.course-inline-add,
.course-skill-add {
    display: flex;
    gap: 0.5rem;
    padding: 0.75rem;
    border-top: 1px solid #E7EBEF;
}
.course-inline-add { display: none; border-top: 0; border-bottom: 1px solid #E7EBEF; }
.course-inline-add.is-open { display: flex; }
.course-inline-add input,
.course-skill-add input { min-width: 0; flex: 1; }
.course-empty-state { padding: 1.5rem 1rem; color: #6c757d; font-size: 0.875rem; text-align: center; }

@media (max-width: 767.98px) {
    .course-builder { grid-template-columns: 1fr; }
    .course-builder-panel { min-height: 220px; }
}

/* Course statistics dialog */
.course-stats-dialog { width: min(760px, 100%); }
.course-stats-body { padding: 1.25rem; }
.course-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 0.75rem;
    margin-bottom: 1.25rem;
}
.course-stat-tile { border: 1px solid #E7EBEF; border-radius: 10px; padding: 0.75rem 0.9rem; }
.course-stat-tile .label { font-size: 0.75rem; color: #667085; }
.course-stat-tile .value { font-size: 1.35rem; font-weight: 700; color: #343A40; line-height: 1.3; }
.course-stat-tile .hint { font-size: 0.7rem; color: #9aa0a6; }
.course-stats-section-title { font-size: 0.875rem; font-weight: 700; margin-bottom: 0.5rem; }
.course-skill-stat { display: grid; grid-template-columns: minmax(0, 1fr) 140px 60px; gap: 0.75rem; align-items: center; padding: 0.4rem 0; border-bottom: 1px solid #EDF0F2; font-size: 0.85rem; }
.course-skill-stat:last-child { border-bottom: 0; }
.course-skill-stat .skill-cat { display: block; font-size: 0.7rem; color: #9aa0a6; }
.course-skill-bar { height: 8px; background: #EDF0F2; border-radius: 99px; overflow: hidden; }
.course-skill-bar > span { display: block; height: 100%; background: #0093E5; }
.course-skill-pct { text-align: right; color: #667085; }
#coursesTable .course-actions { display: flex; gap: 0.4rem; justify-content: center; }
</style>

<!-- =========================================
     WHOLE COURSES CONTAINER
========================================= -->

<div class="courses-container" id="coursesContainer">

    <!-- =====================================
         HEADER CONTAINER
    ====================================== -->

    <div class="courses-header-container" id="coursesHeaderContainer">

        <div class="courses-header" id="coursesHeader">

            <!-- HEADER TEXT -->

            <div>

                <h2 class="mb-1">Courses</h2>

                <div class="text-muted">Manage schedules, enrolments, and class assignments</div>
            </div>

            <!-- ADD NEW COURSE -->

            <button type="button" class="btn btn-primary" id="addCourseButton">

                <span>+</span>

                Add New Course
            </button>
        </div>
    </div>

    <!-- =====================================
         TABLE CONTAINER
    ====================================== -->

    <div class="courses-table-container" id="coursesTableContainer">

        <!-- =================================
             TABLE TOOLBAR
        ================================== -->

        <div class="courses-table-toolbar" id="coursesTableToolbar">

            <!-- SEARCH -->

            <div class="course-search-container" id="courseSearchContainer">

                <i class="bi bi-search"></i>

                <input type="search" id="courseSearch" name="course_search" placeholder="Search" autocomplete="off">
            </div>
        </div>

        <!-- =================================
             TABLE WRAPPER
        ================================== -->

        <div class="courses-table-wrapper" id="coursesTableWrapper">

            <table id="coursesTable">

                <thead>

                    <tr>

                        <th>Course name</th>

                        <th>Sessions</th>

                        <th>Skill Assessment</th>

                        <th>Price</th>

                        <th>Currently enrolled</th>

                        <th>Graduates</th>

                        <th>Action</th>
                    </tr>
                </thead>

                <!-- Edit buttons below open the modal via loadCourseForEdit(). -->
                <tbody id="coursesTableBody">

                    @foreach ($courses as $course)

                        <tr id="courseRow-{{ $course->course_id }}">

                            <td>
                                <span class="course-name">{{ $course->course_name }}</span>
                            </td>

                            <td>{{ $course->course_session_count }}</td>

                            <td>{{ $course->skill_count }}</td>

                            <td>₱{{ number_format($course->course_price, 0) }}</td>

                            <td>{{ $enrolledByCourse->get($course->course_id, 0) }}</td>

                            <td>{{ $graduatesByCourse->get($course->course_id, 0) }}</td>

                            <td>

                                <div class="course-actions">
                                    <button type="button" class="btn btn-outline-secondary stats-course-button" data-course-id="{{ $course->course_id }}">
                                        Stats
                                    </button>
                                    <button type="button" class="btn btn-outline-primary edit-course-button" data-course-id="{{ $course->course_id }}">
                                        Edit
                                    </button>
                                </div>
                            </td>
                        </tr>

                    @endforeach

                    <tr id="courseSearchEmpty" hidden>
                        <td colspan="7" class="course-empty-state">No courses match your search.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ADD COURSE DIALOG -->
<div class="modalOverlay" id="courseModal" hidden>
    <div class="course-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="courseModalTitle">
        <div class="course-modal-header">
            <h2 class="mb-1" id="courseModalTitle">Add a New Course</h2>
            <p class="text-muted mb-0">Add course details, skill categories, and assessments.</p>
        </div>

        <form id="addCourseForm">
            <div class="course-builder">
                <section class="course-builder-panel">
                    <div class="course-builder-panel-heading">COURSE DETAILS</div>
                    <div class="course-details">
                        <div class="mb-3">
                            <label class="form-label" for="courseName">Course Name</label>
                            <input class="form-control" type="text" id="courseName" name="course_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="courseSessions">Sessions</label>
                            <input class="form-control" type="number" id="courseSessions" name="sessions" min="1" required>
                        </div>
                        <div>
                            <label class="form-label" for="coursePrice">Price</label>
                            <input class="form-control" type="number" id="coursePrice" name="price" min="0" step="0.01" required>
                        </div>
                    </div>
                </section>

                <section class="course-builder-panel">
                    <div class="course-builder-panel-heading">
                        <span>Skill Categories</span>
                        <button class="btn btn-outline-primary btn-sm" type="button" id="showCategoryInput" aria-label="Add category">+</button>
                    </div>
                    <div class="course-inline-add" id="categoryInlineAdd">
                        <input class="form-control form-control-sm" type="text" id="categoryName" name="category_name" placeholder="New category name">
                        <button class="btn btn-primary btn-sm" type="button" id="addCategoryButton">Add</button>
                    </div>
                    <div class="course-category-list" id="courseCategoryList"></div>
                </section>

                <section class="course-builder-panel">
                    <div class="course-builder-panel-heading" id="courseSkillsHeading">Skills</div>
                    <div class="course-skill-list" id="courseSkillList"></div>
                    <div class="course-skill-add" id="courseSkillAdd" hidden>
                        <input class="form-control form-control-sm" type="text" id="skillName" name="skill_name" placeholder="Add a skill">
                        <button class="btn btn-primary btn-sm" type="button" id="addSkillButton">Add</button>
                    </div>
                </section>
            </div>

            <div class="course-modal-footer">
                <button class="btn btn-outline-danger" type="button" id="cancelAddCourse">Cancel</button>
                <button class="btn btn-success" type="submit" id="saveCourseButton">Save Course</button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    const modal = document.getElementById('courseModal');
    const form = document.getElementById('addCourseForm');
    const tableBody = document.getElementById('coursesTableBody');
    const courseSearch = document.getElementById('courseSearch');
    const categoryList = document.getElementById('courseCategoryList');
    const skillList = document.getElementById('courseSkillList');
    const skillsHeading = document.getElementById('courseSkillsHeading');
    const skillAdd = document.getElementById('courseSkillAdd');
    const categoryInput = document.getElementById('categoryName');
    const skillInput = document.getElementById('skillName');
    let categories = [];
    let selectedCategory = null;
    let editingCourseId = null;
    let editingCategoryIndex = null;
    let editingSkillIndex = null;

    function getCsrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.content : '';
    }

    // Case-insensitive, trim-insensitive name comparison used for duplicate checks.
    function namesMatch(a, b) {
        return a.trim().toLowerCase() === b.trim().toLowerCase();
    }

    // Opens an empty form for Add, or fills it with a course for Edit.
    function openModal(course) {
        if (course) {
            editingCourseId = course.id;
            form.elements.course_name.value = course.name;
            form.elements.sessions.value = course.sessions;
            form.elements.price.value = course.price;
            categories = structuredClone(course.categories);
            selectedCategory = categories.length ? 0 : null;
            editingCategoryIndex = null;
            editingSkillIndex = null;
            document.getElementById('courseModalTitle').textContent = 'Edit Course';
            document.getElementById('saveCourseButton').textContent = 'Save Changes';
            render();
        } else {
            form.reset();
            categories = [];
            selectedCategory = null;
            editingCourseId = null;
            editingCategoryIndex = null;
            editingSkillIndex = null;
            document.getElementById('courseModalTitle').textContent = 'Add a New Course';
            document.getElementById('saveCourseButton').textContent = 'Save Course';
            render();
        }
        modal.hidden = false;
        document.getElementById('courseName').focus();
    }

    function closeModal() {
        modal.hidden = true;
        form.reset();
        categories = [];
        selectedCategory = null;
        editingCourseId = null;
        editingCategoryIndex = null;
        editingSkillIndex = null;
        document.getElementById('categoryInlineAdd').classList.remove('is-open');
        render();
    }

    function createButton(label, className, handler, ariaLabel) {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = className;
        button.textContent = label;
        button.setAttribute('aria-label', ariaLabel);
        button.addEventListener('click', handler);
        return button;
    }

    // Builds an inline "editing" row: a text input plus save (✓) and cancel (×) buttons.
    function createEditRow(currentValue, onSave, onCancel) {
        const row = document.createElement('div');
        row.className = 'course-list-row course-list-row-editing';

        const input = document.createElement('input');
        input.type = 'text';
        input.className = 'form-control form-control-sm course-list-row-edit-input';
        input.value = currentValue;

        const actions = document.createElement('div');
        actions.className = 'course-list-row-actions';
        const save = createButton('✓', 'btn btn-success btn-sm course-icon-button', function (event) {
            event.stopPropagation();
            onSave(input.value);
        }, 'Save');
        const cancel = createButton('×', 'btn btn-danger btn-sm course-icon-button', function (event) {
            event.stopPropagation();
            onCancel();
        }, 'Cancel');
        actions.append(save, cancel);

        input.addEventListener('click', function (event) { event.stopPropagation(); });
        input.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') { event.preventDefault(); onSave(input.value); }
            if (event.key === 'Escape') { event.preventDefault(); onCancel(); }
        });

        row.append(input, actions);
        queueMicrotask(function () { input.focus(); input.select(); });
        return row;
    }

    function renderCategories() {
        categoryList.replaceChildren();
        if (!categories.length) {
            const empty = document.createElement('div');
            empty.className = 'course-empty-state';
            empty.textContent = 'No categories yet. Use + to add one.';
            categoryList.append(empty);
            return;
        }

        categories.forEach(function (category, index) {
            if (index === editingCategoryIndex) {
                categoryList.append(createEditRow(
                    category.name,
                    function (value) { commitCategoryEdit(index, value); },
                    function () { editingCategoryIndex = null; render(); }
                ));
                return;
            }

            const row = document.createElement('div');
            row.className = 'course-list-row'
                + (index === selectedCategory ? ' is-selected' : '')
                + (category.deleted ? ' is-archived' : '');
            row.addEventListener('click', function () { selectedCategory = index; render(); });

            const name = document.createElement('span');
            name.className = 'course-list-row-name';
            if (category.deleted) {
                const badge = document.createElement('i');
                badge.className = 'bi bi-archive course-archived-badge';
                badge.setAttribute('title', 'Archived');
                name.append(badge);
            }
            name.append(category.name);

            const count = document.createElement('span');
            count.className = 'course-list-row-count';
            count.textContent = category.skills.filter(function (skill) { return !skill.deleted; }).length;

            const actions = document.createElement('div');
            actions.className = 'course-list-row-actions';

            if (category.deleted) {
                // Already saved and archived — the only thing left to do is restore it.
                const restore = createButton('↺', 'btn btn-outline-warning btn-sm course-icon-button', function (event) {
                    event.stopPropagation();
                    category.deleted = false;
                    render();
                }, 'Restore ' + category.name);
                actions.append(restore);
            } else {
                const edit = createButton('✎', 'btn btn-outline-secondary btn-sm course-icon-button', function (event) {
                    event.stopPropagation();
                    editingCategoryIndex = index;
                    editingSkillIndex = null;
                    render();
                }, 'Edit ' + category.name);
                const remove = createButton('×', 'btn btn-outline-danger btn-sm course-icon-button', function (event) {
                    event.stopPropagation();
                    if (category.id !== null) {
                        // Already saved to the database — archive it instead of losing its history.
                        category.deleted = true;
                        if (editingCategoryIndex === index) editingCategoryIndex = null;
                        render();
                        return;
                    }
                    categories.splice(index, 1);
                    if (selectedCategory === index) selectedCategory = null;
                    if (selectedCategory !== null && selectedCategory > index) selectedCategory--;
                    if (editingCategoryIndex === index) editingCategoryIndex = null;
                    render();
                }, category.id !== null ? 'Archive ' + category.name : 'Remove ' + category.name);
                actions.append(edit, remove);
            }

            row.append(name, count, actions);
            categoryList.append(row);
        });
    }

    function commitCategoryEdit(index, value) {
        const name = value.trim();
        if (!name) { editingCategoryIndex = null; render(); return; }
        const clash = categories.find(function (category, i) {
            return i !== index && namesMatch(category.name, name);
        });
        if (clash) {
            showToast(clash.deleted
                ? 'A category named "' + name + '" already exists but is archived. Restore it instead of renaming this one.'
                : 'A category named "' + name + '" already exists.', 'danger');
            return;
        }
        categories[index].name = name;
        editingCategoryIndex = null;
        render();
    }

    function renderSkills() {
        skillList.replaceChildren();
        if (selectedCategory === null || !categories[selectedCategory]) {
            skillsHeading.textContent = 'Skills';
            skillAdd.hidden = true;
            const empty = document.createElement('div');
            empty.className = 'course-empty-state';
            empty.textContent = 'Select a category to view and add its skills.';
            skillList.append(empty);
            return;
        }

        const category = categories[selectedCategory];
        skillsHeading.textContent = 'Skills — ' + category.name;
        skillAdd.hidden = category.deleted;
        if (!category.skills.length) {
            const empty = document.createElement('div');
            empty.className = 'course-empty-state';
            empty.textContent = category.deleted
                ? 'This category is archived.'
                : 'No skills yet. Add the first one below.';
            skillList.append(empty);
            return;
        }

        category.skills.forEach(function (skill, index) {
            if (index === editingSkillIndex) {
                skillList.append(createEditRow(
                    skill.name,
                    function (value) { commitSkillEdit(index, value); },
                    function () { editingSkillIndex = null; render(); }
                ));
                return;
            }

            const row = document.createElement('div');
            row.className = 'course-list-row' + (skill.deleted ? ' is-archived' : '');
            const name = document.createElement('span');
            name.className = 'course-list-row-name';
            if (skill.deleted) {
                const badge = document.createElement('i');
                badge.className = 'bi bi-archive course-archived-badge';
                badge.setAttribute('title', 'Archived');
                name.append(badge);
            }
            name.append(skill.name);

            const actions = document.createElement('div');
            actions.className = 'course-list-row-actions';

            if (skill.deleted) {
                // Already saved and archived — the only thing left to do is restore it.
                const restore = createButton('↺', 'btn btn-outline-warning btn-sm course-icon-button', function () {
                    skill.deleted = false;
                    render();
                }, 'Restore ' + skill.name);
                actions.append(restore);
            } else {
                const edit = createButton('✎', 'btn btn-outline-secondary btn-sm course-icon-button', function () {
                    editingSkillIndex = index;
                    render();
                }, 'Edit ' + skill.name);
                const remove = createButton('×', 'btn btn-outline-danger btn-sm course-icon-button', function () {
                    if (skill.id !== null) {
                        // Already saved to the database — archive it instead of losing its history.
                        skill.deleted = true;
                        if (editingSkillIndex === index) editingSkillIndex = null;
                        render();
                        return;
                    }
                    category.skills.splice(index, 1);
                    if (editingSkillIndex === index) editingSkillIndex = null;
                    render();
                }, skill.id !== null ? 'Archive ' + skill.name : 'Remove ' + skill.name);
                actions.append(edit, remove);
            }

            row.append(name, actions);
            skillList.append(row);
        });
    }

    function commitSkillEdit(index, value) {
        const category = categories[selectedCategory];
        const name = value.trim();
        if (!name) { editingSkillIndex = null; render(); return; }
        const clash = category.skills.find(function (skill, i) {
            return i !== index && namesMatch(skill.name, name);
        });
        if (clash) {
            showToast(clash.deleted
                ? 'A skill named "' + name + '" already exists in this category but is archived. Restore it instead of renaming this one.'
                : 'A skill named "' + name + '" already exists in this category.', 'danger');
            return;
        }
        category.skills[index].name = name;
        editingSkillIndex = null;
        render();
    }

    function render() { renderCategories(); renderSkills(); }

    function addCategory() {
        const name = categoryInput.value.trim();
        if (!name) return;
        const clash = categories.find(function (category) { return namesMatch(category.name, name); });
        if (clash) {
            showToast(clash.deleted
                ? 'A category named "' + name + '" already exists but is archived. Restore it instead of adding a new one.'
                : 'A category named "' + name + '" already exists.', 'danger');
            return;
        }
        categories.push({ id: null, name: name, deleted: false, skills: [] });
        categoryInput.value = '';
        selectedCategory = categories.length - 1;
        document.getElementById('categoryInlineAdd').classList.remove('is-open');
        render();
    }

    function addSkill() {
        const name = skillInput.value.trim();
        if (!name || selectedCategory === null) return;
        const category = categories[selectedCategory];
        if (category.deleted) return;
        const clash = category.skills.find(function (skill) { return namesMatch(skill.name, name); });
        if (clash) {
            showToast(clash.deleted
                ? 'A skill named "' + name + '" already exists in this category but is archived. Restore it instead of adding a new one.'
                : 'A skill named "' + name + '" already exists in this category.', 'danger');
            return;
        }
        category.skills.push({ id: null, name: name, deleted: false });
        skillInput.value = '';
        render();
    }

    // Fetches a course's full details and opens the modal in edit mode.
    async function loadCourseForEdit(button) {
        const courseId = button.dataset.courseId;
        const originalText = button.textContent;
        button.disabled = true;
        button.textContent = 'Loading...';

        try {
            // Adjust this URL to wherever you register the GET route for
            // CourseController@show in web.php.
            const response = await fetch('{{ url('/courses') }}/' + encodeURIComponent(courseId), {
                headers: { 'Accept': 'application/json' },
            });
            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Failed to load course.');
            }

            openModal({
                id: result.course.course_id,
                name: result.course.course_name,
                sessions: result.course.course_session_count,
                price: result.course.course_price,
                categories: result.course.categories
            });

        } catch (error) {
            console.error(error);
            showToast(error.message || 'Something went wrong while loading the course.', 'danger');
        } finally {
            button.disabled = false;
            button.textContent = originalText;
        }
    }

    // Connects the static controls to the mock course builder.
    document.getElementById('addCourseButton').addEventListener('click', function () { openModal(); });
    tableBody.addEventListener('click', function (event) {
        const button = event.target.closest('.edit-course-button');
        if (button) loadCourseForEdit(button);
    });

    // Live search across every visible column (name, sessions, skills, price, etc).
    const searchEmptyRow = document.getElementById('courseSearchEmpty');
    courseSearch.addEventListener('input', function () {
        const query = courseSearch.value.trim().toLowerCase();
        let visibleCount = 0;

        Array.from(tableBody.children).forEach(function (row) {
            if (row === searchEmptyRow) return;
            const matches = !query || row.textContent.toLowerCase().includes(query);
            row.hidden = !matches;
            if (matches) visibleCount++;
        });

        searchEmptyRow.hidden = visibleCount > 0;
    });
    document.getElementById('cancelAddCourse').addEventListener('click', closeModal);
    document.getElementById('showCategoryInput').addEventListener('click', function () {
        document.getElementById('categoryInlineAdd').classList.add('is-open');
        categoryInput.focus();
    });
    document.getElementById('addCategoryButton').addEventListener('click', addCategory);
    document.getElementById('addSkillButton').addEventListener('click', addSkill);
    categoryInput.addEventListener('keydown', function (event) { if (event.key === 'Enter') { event.preventDefault(); addCategory(); } });
    skillInput.addEventListener('keydown', function (event) { if (event.key === 'Enter') { event.preventDefault(); addSkill(); } });
    document.addEventListener('keydown', function (event) { if (event.key === 'Escape' && !modal.hidden) closeModal(); });

    form.addEventListener('submit', async function (event) {
        event.preventDefault();

        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const formData = new FormData(form);
        const data = {
            course_name: formData.get('course_name').trim(),
            course_session_count: Number(formData.get('sessions')),
            course_price: Number(formData.get('price')),
            categories: categories
        };

        const isEditing = editingCourseId !== null;

        // Only warn about a duplicate name when adding a new course, not when editing one.
        if (!isEditing) {
            const existingNames = Array.from(document.querySelectorAll('.course-name'))
                .map(function (el) { return el.textContent; });
            const isDuplicate = existingNames.some(function (existing) { return namesMatch(existing, data.course_name); });
            if (isDuplicate && !confirm('A course named "' + data.course_name + '" already exists. Add it anyway?')) {
                return;
            }
        }

        const saveButton = document.getElementById('saveCourseButton');
        const originalText = saveButton.textContent;
        saveButton.disabled = true;
        saveButton.textContent = 'Saving...';

        // Adjust these URLs to wherever you register the POST/PUT routes
        // for CourseController@store / @update in web.php.
        const endpoint = isEditing
            ? '{{ url('/courses') }}/' + editingCourseId
            : '{{ url('/courses') }}';

        try {
            const response = await fetch(endpoint, {
                method: isEditing ? 'PUT' : 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                },
                body: JSON.stringify(data)
            });

            const responseText = await response.text();
            let result;

            try {
                result = JSON.parse(responseText);
            } catch (error) {
                console.error('Server did not return JSON:', responseText);
                throw new Error('Server returned HTML instead of JSON. Check the console.');
            }

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Failed to save course.');
            }

            // Reload so the page picks up the up-to-date course list.
            showToast(isEditing ? 'Course updated successfully!' : 'Course added successfully!', 'success');
            setTimeout(function () { window.location.reload(); }, 1000);

        } catch (error) {
            console.error(error);
            showToast(error.message || 'Something went wrong while saving the course.', 'danger');
            saveButton.disabled = false;
            saveButton.textContent = originalText;
        }
    });

    render();
}());
</script>

<!-- COURSE STATISTICS DIALOG -->
<div class="modalOverlay" id="courseStatsModal" hidden>
    <div class="course-modal-dialog course-stats-dialog" role="dialog" aria-modal="true" aria-labelledby="courseStatsTitle">
        <div class="course-modal-header">
            <h2 class="mb-1" id="courseStatsTitle">Course Statistics</h2>
            <p class="text-muted mb-0" id="courseStatsSubtitle"></p>
        </div>
        <div class="course-stats-body" id="courseStatsBody"></div>
        <div class="course-modal-footer">
            <button class="btn btn-outline-secondary" type="button" id="closeCourseStats">Close</button>
        </div>
    </div>
</div>

<script>
(function () {
    const modal = document.getElementById('courseStatsModal');
    const body = document.getElementById('courseStatsBody');
    const title = document.getElementById('courseStatsTitle');
    const subtitle = document.getElementById('courseStatsSubtitle');
    const peso = new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP', maximumFractionDigits: 0 });

    function el(tag, className, text) {
        const node = document.createElement(tag);
        if (className) node.className = className;
        if (text !== undefined) node.textContent = text;
        return node;
    }

    function tile(label, value, hint) {
        const t = el('div', 'course-stat-tile');
        t.appendChild(el('div', 'label', label));
        t.appendChild(el('div', 'value', value));
        if (hint) t.appendChild(el('div', 'hint', hint));
        return t;
    }

    function render(stats) {
        body.replaceChildren();

        const grid = el('div', 'course-stats-grid');
        grid.appendChild(tile('Currently enrolled', stats.active, 'Not graduated or dropped'));
        grid.appendChild(tile('Ready to graduate', stats.ready_to_grad, 'Latest assessment says ready'));
        grid.appendChild(tile('Graduates', stats.graduated));
        grid.appendChild(tile('Dropped', stats.dropped));
        grid.appendChild(tile('Completion rate',
            stats.completion_rate === null ? '—' : stats.completion_rate + '%',
            'Graduates ÷ (graduates + dropped)'));
        grid.appendChild(tile('Avg. time to graduate',
            stats.avg_days_to_graduate === null ? '—' : stats.avg_days_to_graduate + ' days'));
        grid.appendChild(tile('Collected', peso.format(stats.collected), 'Excludes archived payments'));
        grid.appendChild(tile('Outstanding', peso.format(stats.outstanding), 'Unpaid balance, excl. dropped'));
        body.appendChild(grid);

        body.appendChild(el('div', 'course-stats-section-title', 'Skill pass rates'));

        if (!stats.skills.length) {
            body.appendChild(el('div', 'course-empty-state', 'This course has no skills yet.'));
            return;
        }

        stats.skills.forEach(function (skill) {
            const row = el('div', 'course-skill-stat');

            const name = el('div');
            name.appendChild(el('span', '', skill.name));
            name.appendChild(el('span', 'skill-cat', skill.category));
            row.appendChild(name);

            const bar = el('div', 'course-skill-bar');
            const fill = el('span');
            fill.style.width = (skill.pass_rate === null ? 0 : skill.pass_rate) + '%';
            bar.appendChild(fill);
            row.appendChild(bar);

            row.appendChild(el('div', 'course-skill-pct',
                skill.pass_rate === null ? 'No data' : skill.pass_rate + '%'));
            row.title = skill.assessed + ' student(s) assessed';

            body.appendChild(row);
        });
    }

    async function openStats(button) {
        const original = button.textContent;
        button.disabled = true;
        button.textContent = 'Loading...';

        try {
            const response = await fetch('{{ url('/courses') }}/' + encodeURIComponent(button.dataset.courseId) + '/stats', {
                headers: { 'Accept': 'application/json' },
            });
            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Failed to load statistics.');
            }

            title.textContent = result.stats.course_name;
            subtitle.textContent = result.stats.enrolled_total + ' enrolment(s) on record, all time.';
            render(result.stats);
            modal.hidden = false;
        } catch (error) {
            console.error(error);
            if (typeof showToast === 'function') showToast(error.message || 'Could not load statistics.', 'danger');
        } finally {
            button.disabled = false;
            button.textContent = original;
        }
    }

    document.getElementById('coursesTableBody').addEventListener('click', function (event) {
        const button = event.target.closest('.stats-course-button');
        if (button) openStats(button);
    });

    document.getElementById('closeCourseStats').addEventListener('click', function () { modal.hidden = true; });
    modal.addEventListener('click', function (event) { if (event.target === modal) modal.hidden = true; });
    document.addEventListener('keydown', function (event) { if (event.key === 'Escape' && !modal.hidden) modal.hidden = true; });
})();
</script>