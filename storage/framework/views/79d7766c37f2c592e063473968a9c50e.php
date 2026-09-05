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
            <form method="POST" action="<?php echo e(route('logout')); ?>">
                <?php echo csrf_field(); ?>
                <button type="submit" class="nav-link text-danger border-0 bg-transparent">
                    <i class="bi bi-box-arrow-right me-2"></i>Logout
                </button>
            </form>
        </li>
    </ul>
</aside><?php /**PATH /var/www/html/resources/views/includes/sidebar.blade.php ENDPATH**/ ?>