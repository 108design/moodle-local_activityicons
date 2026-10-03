<?php
require('../../config.php');
require_once($CFG->libdir . '/adminlib.php');

require_login();
require_capability('local/activityicons:managepool', context_system::instance());
admin_externalpage_setup('local_activityicons');
$PAGE->requires->css('/local/activityicons/styles.css');
$url = new moodle_url('/local/activityicons/pool.php');
$upload = new \local_activityicons\form\pool_icon($url);
$form = new \local_activityicons\form\administration($url);
if ($data = $upload->get_data()) {
    $count = \local_activityicons\local\administration::import_draft((int) $data->iconfiles);
    redirect($url, get_string('batchsaved', 'local_activityicons', $count), null,
        \core\output\notification::NOTIFY_SUCCESS);
}
if ($data = $form->get_data()) {
    \local_activityicons\local\administration::save($data);
    redirect($url, get_string('adminsaved', 'local_activityicons'), null,
        \core\output\notification::NOTIFY_SUCCESS);
}
$form->set_data(\local_activityicons\local\administration::defaults());
$PAGE->requires->js_call_amd('local_activityicons/mapping', 'init', [[
    'catalog' => \local_activityicons\local\icon_catalog::client_entries($PAGE->get_renderer('core')),
    'choose' => get_string('chooseicon', 'local_activityicons'),
    'chooseType' => get_string('chooseh5ptype', 'local_activityicons'),
    'close' => get_string('closepicker', 'local_activityicons'),
    'search' => get_string('searchicons', 'local_activityicons'),
    'remove' => get_string('removemapping', 'local_activityicons'),
    'undoRemove' => get_string('undoremovemapping', 'local_activityicons'),
    'deleteSelected' => get_string('deleteiconsselected', 'local_activityicons'),
]]);
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'local_activityicons'));
echo html_writer::tag('p', get_string('adminintro', 'local_activityicons'));
$upload->display();
$form->display();
echo $OUTPUT->footer();
