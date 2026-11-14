<?php
// This file is part of Moodle - https://moodle.org/
/**
 * This file is part of a 108design source-available software product.
 *
 * Use and modification are permitted only under the Software License included
 * with this distribution. See LICENSE.md for the full terms.
 *
 * File documentation for db/hooks.php.
 *
 * @package   local_activityicons
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license   See LICENSE.md for the full terms.
 */
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

$callbacks[] = [
    'hook' => \core\hook\after_config::class,
    'callback' => [\local_activityicons\local\update_guard::class, 'after_config'],
    'priority' => 1002,
];
