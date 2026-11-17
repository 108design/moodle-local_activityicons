<?php
// This file is part of Moodle - https://moodle.org/
/**
 * This file is part of a 108design source-available software product.
 *
 * Use and modification are permitted only under the Software License included
 * with this distribution. See LICENSE.md for the full terms.
 *
 * File documentation for mapping.php.
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

$url = new moodle_url('/local/activityicons/mapping.php');
admin_page::prepare($url);
$form = new \local_activityicons\form\h5p_mapping($url);
if ($data = $form->get_data()) {
    administration::save_mapping($data);
    redirect($url, get_string('adminsaved', 'local_activityicons'), null, \core\output\notification::NOTIFY_SUCCESS);
}
$form->set_data(administration::mapping_defaults());
$PAGE->requires->js_call_amd('local_activityicons/mapping', 'init', [[
    'catalog' => [
        ['key' => 'default', 'label' => get_string('h5pfallbackdefault', 'local_activityicons')],
        ...\local_activityicons\local\icon_catalog::client_entries($PAGE->get_renderer('core')),
    ],
    'choose' => get_string('chooseicon', 'local_activityicons'),
    'chooseType' => get_string('chooseh5ptype', 'local_activityicons'),
    'chooseFallback' => get_string('h5pfallback', 'local_activityicons'),
    'close' => get_string('closepicker', 'local_activityicons'),
    'search' => get_string('searchicons', 'local_activityicons'),
    'pools' => \local_activityicons\local\icon_catalog::pools(),
    'pool' => get_string('poolfilter', 'local_activityicons'),
    'allPools' => get_string('allpools', 'local_activityicons'),
    'noResults' => get_string('noiconresults', 'local_activityicons'),
    'remove' => get_string('removemapping', 'local_activityicons'),
    'undoRemove' => get_string('undoremovemapping', 'local_activityicons'),
]]);
echo $OUTPUT->header();
echo admin_page::tabs('mapping');
echo $OUTPUT->heading(get_string('tabmapping', 'local_activityicons'), 2);
echo html_writer::tag('p', get_string('mappinghelp', 'local_activityicons') . context_help::icon('mapping'), ['class' => 'mb-4']);
$form->display();
echo context_help::render();
echo $OUTPUT->footer();
