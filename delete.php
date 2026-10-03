<?php
require('../../config.php');
require_once($CFG->libdir . '/adminlib.php');

require_login();
require_capability('local/activityicons:managepool', context_system::instance());
$url = new moodle_url('/local/activityicons/delete.php');
$returnurl = new moodle_url('/local/activityicons/pool.php');
admin_externalpage_setup('local_activityicons', '', [], $url);
$ids = array_map('intval', array_filter(explode(',', optional_param('ids', '', PARAM_SEQUENCE))));
if (!$ids && ($id = optional_param('id', 0, PARAM_INT))) {
    $ids[] = $id;
} else if (!$ids && data_submitted()) {
    require_sesskey();
    foreach (optional_param_array('deleteicons', [], PARAM_BOOL) as $id => $selected) {
        if ($selected && ctype_digit((string) $id)) {
            $ids[] = (int) $id;
        }
    }
}
$ids = array_values(array_unique(array_filter($ids)));
if (!$ids || count($ids) > 500) {
    redirect($returnurl, get_string('selecticonsfirst', 'local_activityicons'), null,
        \core\output\notification::NOTIFY_WARNING);
}
$form = new \local_activityicons\form\delete_icons($url, ['ids' => implode(',', $ids)]);
if ($form->is_cancelled()) {
    redirect($returnurl);
}
if ($data = $form->get_data()) {
    $result = \local_activityicons\local\pool::delete_unused_batch(explode(',', $data->ids));
    $message = get_string('iconsdeletedbatch', 'local_activityicons', (object) $result);
    $type = $result['kept'] ? \core\output\notification::NOTIFY_WARNING :
        \core\output\notification::NOTIFY_SUCCESS;
    redirect($returnurl, $message, null, $type);
}
$records = $DB->get_records_list(\local_activityicons\local\pool::TABLE, 'id', $ids);
if (!$records) {
    redirect($returnurl, get_string('selecticonsfirst', 'local_activityicons'), null,
        \core\output\notification::NOTIFY_WARNING);
}
$used = \local_activityicons\local\pool::used_keys();
$locked = count(array_filter($records, fn($record) => isset($used[$record->iconkey])));
$form = new \local_activityicons\form\delete_icons($url, ['ids' => implode(',', array_keys($records))]);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('deleteicons', 'local_activityicons'));
echo html_writer::tag('p', get_string('deleteiconsconfirm', 'local_activityicons', (object) [
    'selected' => count($records),
    'locked' => $locked,
]));
$form->display();
echo $OUTPUT->footer();
