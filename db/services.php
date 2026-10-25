<?php
/**
 * This file is part of a 108design source-available software product.
 *
 * Use and modification are permitted only under the Software License included
 * with this distribution. See LICENSE.md for the full terms.
 *
 * File documentation for db/services.php.
 *
 * @package   local_activityicons
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license   See LICENSE.md for the full terms.
 */
defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_activityicons_set_icon' => [
        'classname' => 'local_activityicons\\external\\set_icon',
        'methodname' => 'execute',
        'description' => 'Set the icon selection for one course module.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'local/activityicons:manage',
    ],
    'local_activityicons_get_icons' => [
        'classname' => 'local_activityicons\\external\\get_icons',
        'methodname' => 'execute',
        'description' => 'Resolve custom activity icons for visible course modules.',
        'type' => 'read',
        'ajax' => true,
    ],
];
