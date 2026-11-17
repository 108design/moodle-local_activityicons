<?php
// This file is part of Moodle - https://moodle.org/
/**
 * This file is part of a 108design source-available software product.
 *
 * Use and modification are permitted only under the Software License included
 * with this distribution. See LICENSE.md for the full terms.
 *
 * File documentation for preferences.php.
 *
 * @package   local_activityicons
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license   See LICENSE.md for the full terms.
 */
require('../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use local_activityicons\local\admin_page;
use local_activityicons\local\administration;
use local_activityicons\local\context_help;

require_login();
require_capability('moodle/site:config', context_system::instance());
$url = new moodle_url('/local/activityicons/preferences.php');
admin_page::prepare($url);
$form = new \local_activityicons\form\preferences($url);
if ($data = $form->get_data()) {
    administration::save_settings($data);
    redirect($url, get_string('adminsaved', 'local_activityicons'), null, \core\output\notification::NOTIFY_SUCCESS);
}
$form->set_data(administration::setting_defaults());
echo $OUTPUT->header();
echo admin_page::tabs('preferences');
echo $OUTPUT->heading(get_string('settings'), 2);
$form->display();
echo context_help::render();
echo $OUTPUT->footer();
