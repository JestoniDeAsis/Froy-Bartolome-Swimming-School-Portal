<aside id="sideMenu" class="side-menu">

    <div class="sidebar-profile d-flex align-items-center gap-2 px-3 py-3 border-bottom">
        <div class="profile-circle d-flex align-items-center justify-content-center rounded-circle bg-secondary text-white flex-shrink-0">
            <?php echo e(strtoupper(substr($profile->first_name ?? '', 0, 1) . substr($profile->last_name ?? '', 0, 1))); ?>

        </div>
        <div class="profile-info overflow-hidden">
            <div class="profile-name text-truncate fw-semibold">
                <?php echo e(strtoupper($profile->last_name ?? '')); ?>, <?php echo e(strtoupper($profile->first_name ?? '')); ?>

                <?php if(!empty($profile->middle_name)): ?>
                    <?php echo e(strtoupper(substr($profile->middle_name, 0, 1))); ?>.
                <?php endif; ?>
            </div>
            <div class="profile-role text-muted small text-truncate">
                <?php echo e($roleName ?? 'No role'); ?>

            </div>
        </div>
    </div>

    <ul class="nav flex-column">
        <?php $__currentLoopData = $menuItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <li class="nav-item">
                <?php if($page === $key): ?>
                    <span class="nav-link active" aria-current="page">
                        <i class="bi <?php echo e($item['icon']); ?> me-2"></i><?php echo e($item['label']); ?>

                    </span>
                <?php else: ?>
                    <a href="<?php echo e(route('home.navigate', ['page' => $key])); ?>" class="nav-link">
                        <i class="bi <?php echo e($item['icon']); ?> me-2"></i><?php echo e($item['label']); ?>

                    </a>
                <?php endif; ?>
            </li>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </ul>

    <ul class="nav flex-column sidebar-footer">
        <li class="nav-item border-top pt-2">
            <form method="POST" action="<?php echo e(route('logout')); ?>" id="logoutForm">
                <?php echo csrf_field(); ?>
                <button type="button" class="nav-link text-danger border-0 bg-transparent" id="logoutButton">
                    <i class="bi bi-box-arrow-right me-2"></i>Logout
                </button>
            </form>
        </li>
    </ul>
</aside>



<div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="logoutModalLabel">Log Out</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to log out?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="logoutForm" class="btn btn-danger" id="confirmLogoutButton">Log Out</button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {

    // Keep one copy of the modal, sitting directly under <body>.
    const modals = Array.from(document.querySelectorAll('#logoutModal'));
    const inBody = modals.find(function (el) { return el.parentElement === document.body; });

    if (inBody) {
        modals.filter(function (el) { return el !== inBody; }).forEach(function (el) { el.remove(); });
    } else if (modals[0]) {
        document.body.appendChild(modals[0]);
    }

    // Attach the listeners only once, even if this partial is injected again.
    if (window.__logoutModalInitialized) {
        return;
    }
    window.__logoutModalInitialized = true;

    document.addEventListener('click', function (event) {
        if (!event.target.closest('#logoutButton')) {
            return;
        }

        const modalEl = document.getElementById('logoutModal');

        if (modalEl && typeof bootstrap !== 'undefined') {
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        } else if (window.confirm('Are you sure you want to log out?')) {
            // Fallback if Bootstrap's JavaScript isn't loaded on this page
            document.getElementById('logoutForm').submit();
        }
    });

    document.addEventListener('submit', function (event) {
        if (event.target.id !== 'logoutForm') {
            return;
        }

        const confirmButton = document.getElementById('confirmLogoutButton');
        if (confirmButton) {
            confirmButton.disabled = true;
            confirmButton.textContent = 'Logging out...';
        }
    });

})();
</script><?php /**PATH C:\xampp\htdocs\Froy-Bartolome-Swimming-School-Portal\resources\views/includes/sidebar.blade.php ENDPATH**/ ?>