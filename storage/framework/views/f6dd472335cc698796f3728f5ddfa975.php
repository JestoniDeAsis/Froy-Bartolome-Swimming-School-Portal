<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>

    <link rel="icon" type="image/png" href="<?php echo e(asset('assets/images/icon_fbss.png')); ?>">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?php echo e(asset('assets/css/layout.css')); ?>" rel="stylesheet">
</head>
<body>

<?php echo $__env->make('includes.topbar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

<div class="app-body">

    <div id="sidebarBackdrop" class="sidebar-backdrop"></div>

    <?php echo $__env->make('includes.sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <main id="displayContainer" class="display-container">
        <?php echo $__env->make($pageView, array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    </main>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo e(asset('assets/js/layout.js')); ?>"></script>
</body>
</html><?php /**PATH C:\xampp\htdocs\Froy-Bartolome-Swimming-School-Portal\resources\views/home/index.blade.php ENDPATH**/ ?>