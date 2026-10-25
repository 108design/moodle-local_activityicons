<?php
/**
 * This file is part of a 108design source-available software product.
 *
 * Use and modification are permitted only under the Software License included
 * with this distribution. See LICENSE.md for the full terms.
 *
 * File documentation for settings.php.
 *
 * @package   local_activityicons
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license   See LICENSE.md for the full terms.
 */
defined('MOODLE_INTERNAL') || die();

// One entry for the complete administration workflow.
$ADMIN->add('localplugins', new admin_externalpage('local_activityicons',
    get_string('pluginname', 'local_activityicons'), new moodle_url('/local/activityicons/pool.php'),
    'local/activityicons:managepool'));
