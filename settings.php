<?php
defined('MOODLE_INTERNAL') || die();

// One entry for the complete administration workflow.
$ADMIN->add('localplugins', new admin_externalpage('local_activityicons',
    get_string('pluginname', 'local_activityicons'), new moodle_url('/local/activityicons/pool.php'),
    'local/activityicons:managepool'));
