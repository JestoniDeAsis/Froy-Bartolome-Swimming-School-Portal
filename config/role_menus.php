<?php
// config/role_menus.php
return [
    1 => [ // admin
        'dashboard'      => ['icon' => 'bi-speedometer2',     'label' => 'Dashboard',        'view' => 'pages.admin.dashboard'],
        'registers'      => ['icon' => 'bi-pencil-square',    'label' => 'Registers',         'view' => 'pages.admin.registers'],
        'attendance'     => ['icon' => 'bi-calendar-check',   'label' => 'Attendance',        'view' => 'pages.admin.attendance'],
        'courses'        => ['icon' => 'bi-journal-bookmark', 'label' => 'Courses',           'view' => 'pages.admin.courses'],
        'coaches'        => ['icon' => 'bi-person-badge',     'label' => 'Coaches',           'view' => 'pages.admin.coaches'],
        'sales_invoice'  => ['icon' => 'bi-receipt',          'label' => 'Sales & Invoice',   'view' => 'pages.admin.sales_and_invoice'],
        'graduation'     => ['icon' => 'bi-mortarboard',      'label' => 'Graduation',        'view' => 'pages.admin.graduation'],
        'announcements'  => ['icon' => 'bi-megaphone',        'label' => 'Announcements',     'view' => 'pages.admin.announcements'],
        'profile'            => ['icon' => 'bi-person-circle',   'label' => 'Profile',           'view' => 'pages.shared.profile'],
    ],
    2 => [ // coach
        'dashboard'          => ['icon' => 'bi-speedometer2',    'label' => 'Dashboard',         'view' => 'pages.coach.dashboard'],
        'attendance'         => ['icon' => 'bi-calendar-check',  'label' => 'Attendance',        'view' => 'pages.coach.attendance'],
        'skills_assessment'  => ['icon' => 'bi-clipboard-check', 'label' => 'Skills Assessment', 'view' => 'pages.coach.skills_assessment'],
        'announcements'      => ['icon' => 'bi-megaphone',       'label' => 'Announcements',     'view' => 'pages.shared.announcements'],
        'profile'            => ['icon' => 'bi-person-circle',   'label' => 'Profile',           'view' => 'pages.shared.profile'],
    ],
    3 => [ // student
        'dashboard'     => ['icon' => 'bi-speedometer2',    'label' => 'Dashboard',     'view' => 'pages.student.dashboard'],
        'attendance'    => ['icon' => 'bi-calendar-check',  'label' => 'Attendance',    'view' => 'pages.student.attendance'],
        'payments'      => ['icon' => 'bi-cash-coin',       'label' => 'Payments',      'view' => 'pages.shared.payments'],
        'certificates'  => ['icon' => 'bi-award',           'label' => 'Certificates',  'view' => 'pages.student.certificates'],
        'announcements' => ['icon' => 'bi-megaphone',       'label' => 'Announcements', 'view' => 'pages.shared.announcements'],
        'profile'       => ['icon' => 'bi-person-circle',   'label' => 'Profile',       'view' => 'pages.shared.profile'],
    ],
    4 => [ // athlete
        'dashboard'     => ['icon' => 'bi-speedometer2',    'label' => 'Dashboard',     'view' => 'pages.athlete.dashboard'],
        'payments'      => ['icon' => 'bi-cash-coin',       'label' => 'Payments',      'view' => 'pages.shared.payments'],
        'announcements' => ['icon' => 'bi-megaphone',       'label' => 'Announcements', 'view' => 'pages.shared.announcements'],
        'profile'       => ['icon' => 'bi-person-circle',   'label' => 'Profile',       'view' => 'pages.shared.profile'],
    ],
];