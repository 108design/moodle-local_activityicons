<?php
defined('MOODLE_INTERNAL') || die();

$callbacks = [
    [
        'hook' => \core\hook\output\before_standard_head_html_generation::class,
        'callback' => [\local_activityicons\local\hook_callbacks::class, 'before_standard_head_html'],
    ],
    [
        'hook' => \core\hook\output\before_http_headers::class,
        'callback' => [\local_activityicons\local\hook_callbacks::class, 'before_http_headers'],
    ],
];

// Decorative product identity is restricted to the native administration surfaces.
$callbacks[] = [
    'hook' => \core\hook\output\before_standard_head_html_generation::class,
    'callback' => [\local_activityicons\local\admin_branding::class, 'head'],
];
$callbacks[] = [
    'hook' => \core\hook\output\before_http_headers::class,
    'callback' => [\local_activityicons\local\admin_branding::class, 'prepare'],
];
