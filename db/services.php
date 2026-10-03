<?php
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
