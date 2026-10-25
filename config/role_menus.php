<?php
// config/role_menus.php
return [
    1 => [ // admin
        'dashboard'     => ['icon' => 'bi-speedometer2',    'label' => 'Dashboard',         'view' => 'pages.admin.dashboard'],
        'attendance'    => ['icon' => 'bi-calendar-check',   'label' => 'Attendance',        'view' => 'pages.admin.attendance'],
        'registers'     => ['icon' => 'bi-people',           'label' => 'Students',          'view' => 'pages.admin.registers'],
        'athletes'      => ['icon' => 'bi-trophy',           'label' => 'Athletes',          'view' => 'pages.admin.athletes'],
        'coaches'       => ['icon' => 'bi-person-badge',     'label' => 'User Management',           'view' => 'pages.admin.coaches'],
        'courses'       => ['icon' => 'bi-journal-bookmark', 'label' => 'Courses',           'view' => 'pages.admin.courses'],
        'sales_invoice' => ['icon' => 'bi-receipt',          'label' => 'Billing & Invoices', 'view' => 'pages.admin.sales_and_invoice'],
        'graduation'    => ['icon' => 'bi-mortarboard',      'label' => 'Graduation',        'view' => 'pages.admin.graduation'],
        'announcements' => ['icon' => 'bi-megaphone',        'label' => 'Announcements',     'view' => 'pages.admin.announcements'],
        'profile'       => ['icon' => 'bi-person-circle',    'label' => 'Profile',           'view' => 'pages.shared.profile'],
    ],
    2 => [ // coach
        'dashboard'          => ['icon' => 'bi-speedometer2',    'label' => 'Dashboard',         'view' => 'pages.coach.dashboard'],
        'attendance'         => ['icon' => 'bi-calendar-check',  'label' => 'Attendance',        'view' => 'pages.coach.attendance'],
        'skills_assessment'  => ['icon' => 'bi-clipboard-check', 'label' => 'Class Assessment', 'view' => 'pages.coach.skills_assessment'],
        'athlete_records'    => ['icon' => 'bi-stopwatch',       'label' => 'Athlete Records',   'view' => 'pages.coach.athlete_records'],
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
        'attendance'    => ['icon' => 'bi-calendar-check',  'label' => 'Attendance',    'view' => 'pages.student.attendance'],
        'payments'      => ['icon' => 'bi-cash-coin',       'label' => 'Payments',      'view' => 'pages.shared.payments'],
        'announcements' => ['icon' => 'bi-megaphone',       'label' => 'Announcements', 'view' => 'pages.shared.announcements'],
        'profile'       => ['icon' => 'bi-person-circle',   'label' => 'Profile',       'view' => 'pages.shared.profile'],
    ],
    5 => [ // applicant
        'dashboard'     => ['icon' => 'bi-speedometer2',    'label' => 'Dashboard',     'view' => 'pages.student.dashboard'],
        // 'attendance'    => ['icon' => 'bi-calendar-check',  'label' => 'Attendance',    'view' => 'pages.student.attendance'],
        // 'payments'      => ['icon' => 'bi-cash-coin',       'label' => 'Payments',      'view' => 'pages.shared.payments'],
        // 'certificates'  => ['icon' => 'bi-award',           'label' => 'Certificates',  'view' => 'pages.student.certificates'],
        'announcements' => ['icon' => 'bi-megaphone',       'label' => 'Announcements', 'view' => 'pages.shared.announcements'],
        'profile'       => ['icon' => 'bi-person-circle',   'label' => 'Profile',       'view' => 'pages.shared.profile'],
    ],
    6 => [ // former athlete
        'dashboard'     => ['icon' => 'bi-speedometer2',    'label' => 'Dashboard',     'view' => 'pages.athlete.dashboard'],
        'attendance'    => ['icon' => 'bi-calendar-check',  'label' => 'Attendance',    'view' => 'pages.student.attendance'],
        'payments'      => ['icon' => 'bi-cash-coin',       'label' => 'Payments',      'view' => 'pages.shared.payments'],
        'announcements' => ['icon' => 'bi-megaphone',       'label' => 'Announcements', 'view' => 'pages.shared.announcements'],
        'profile'       => ['icon' => 'bi-person-circle',   'label' => 'Profile',       'view' => 'pages.shared.profile'],
    ],
];