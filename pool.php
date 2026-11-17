<?php
// This file is part of Moodle - https://moodle.org/
/**
 * This file is part of a 108design source-available software product.
 *
 * Use and modification are permitted only under the Software License included
 * with this distribution. See LICENSE.md for the full terms.
 *
 * File documentation for pool.php.
 *
 * @package   local_activityicons
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license   See LICENSE.md for the full terms.
 */

require('../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use local_activityicons\form\pool_icon;
use local_activityicons\local\admin_page;
use local_activityicons\local\administration;
use local_activityicons\local\context_help;
use local_activityicons\local\pool_browser;
use local_activityicons\local\pool;
use local_activityicons\output\pool_browser as browser_output;

$key = optional_param('pool', '', PARAM_ALPHANUMEXT);
$url = pool_browser::url($key);
admin_page::prepare($url);
$browser = new browser_output($PAGE->get_renderer('core'));
$view = $key === '' ? null : new pool_browser($key, $PAGE->get_renderer('core'));
$upload = null;
if ($view === null || $view->groupid !== null) {
    $upload = new pool_icon($url, ['destination' => $view?->groupid]);
    if ($data = $upload->get_data()) {
        $destination = $view?->groupid ?? (int) ($data->destinationpool ?? 0);
        $count = administration::import_draft((int) $data->iconfiles, $destination);
        redirect($url, get_string('batchsaved', 'local_activityicons', $count), null,
            \core\output\notification::NOTIFY_SUCCESS);
    }
}
$PAGE->requires->js_call_amd('local_activityicons/mapping', 'initPool');
echo $OUTPUT->header();
echo admin_page::tabs('pool');
if ($view !== null) {
    echo $OUTPUT->heading(s($view->label), 2, 'mb-4');
}
if (pool::developer_mode_enabled() && $view?->has_used_uploads()) {
    echo $OUTPUT->notification(get_string('developermodeactive', 'local_activityicons'), \core\output\notification::NOTIFY_WARNING);
}
if ($upload !== null) {
    echo html_writer::start_tag('section', ['class' => 'mb-4']);
    $upload->display();
    echo html_writer::end_tag('section');
}
echo $view === null ? $browser->overview() : $browser->contents($view);
echo context_help::render();
echo $OUTPUT->footer();
