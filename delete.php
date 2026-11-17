<?php
// This file is part of Moodle - https://moodle.org/
/**
 * This file is part of a 108design source-available software product.
 *
 * Use and modification are permitted only under the Software License included
 * with this distribution. See LICENSE.md for the full terms.
 *
 * File documentation for delete.php.
 *
 * @package   local_activityicons
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license   See LICENSE.md for the full terms.
 */
require('../../config.php');
require_once($CFG->libdir . '/adminlib.php');

require_login();
require_capability('local/activityicons:managepool', context_system::instance());
$url = new moodle_url('/local/activityicons/delete.php');
$returnpool = optional_param('returnpool', '', PARAM_ALPHANUMEXT);
$returnurl = \local_activityicons\local\pool_browser::url(
    isset(\local_activityicons\local\icon_catalog::pools()[$returnpool]) ? $returnpool : ''
);
if ($returnpool !== '') {
    $url->param('returnpool', $returnpool);
}
\local_activityicons\local\admin_page::prepare($url);
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
$records = $DB->get_records_list(\local_activityicons\local\pool::TABLE, 'id', $ids, 'label, id');
if (!$records) {
    redirect($returnurl, get_string('selecticonsfirst', 'local_activityicons'), null,
        \core\output\notification::NOTIFY_WARNING);
}
$usage = \local_activityicons\local\pool::deletion_usage($records);
$locked = count(array_filter($usage, static fn($item) => $item['assignments'] || $item['types'] || $item['fallback']));
$requestedforce = data_submitted() && optional_param('force', 0, PARAM_BOOL);
$enabled = \local_activityicons\local\pool::developer_mode_enabled();
if ($requestedforce && !$enabled) {
    require_sesskey();
    require_capability('moodle/site:config', context_system::instance());
    redirect($returnurl, get_string('developermodedisabled', 'local_activityicons'), null,
        \core\output\notification::NOTIFY_WARNING);
}
$force = $enabled && ($locked || $requestedforce);
$form = new \local_activityicons\form\delete_icons($url, [
    'ids' => implode(',', array_keys($records)),
    'force' => $force,
    'usagehash' => \local_activityicons\local\pool::usage_hash($usage),
]);
if ($form->is_cancelled()) {
    redirect($returnurl);
}
if ($data = $form->get_data()) {
    $forced = !empty($data->force);
    try {
        $result = \local_activityicons\local\pool::delete_unused_batch(explode(',', $data->ids), $forced, $data->usagehash);
    } catch (moodle_exception $exception) {
        if ($exception->errorcode !== 'deleteusagechanged') {
            throw $exception;
        }
        redirect(new moodle_url($url, ['ids' => $data->ids]), get_string('deleteusagechanged', 'local_activityicons'),
            null, \core\output\notification::NOTIFY_WARNING);
    }
    $message = get_string($forced ? 'iconsdeletedreset' : 'iconsdeletedbatch', 'local_activityicons', (object) $result);
    $type = $result['kept'] ? \core\output\notification::NOTIFY_WARNING :
        \core\output\notification::NOTIFY_SUCCESS;
    redirect($returnurl, $message, null, $type);
}

echo $OUTPUT->header();
echo \local_activityicons\local\admin_page::tabs('pool');
echo $OUTPUT->heading(get_string('deleteicons', 'local_activityicons'));
if ($force) {
    echo $OUTPUT->notification(get_string('deleteusedwarning', 'local_activityicons'), \core\output\notification::NOTIFY_WARNING,
        false);
    echo html_writer::tag('p', get_string('deleteusageintro', 'local_activityicons') .
        \local_activityicons\local\context_help::icon('developer'));
} else {
    echo html_writer::tag('p', get_string('deleteiconsconfirm', 'local_activityicons', (object) [
        'selected' => count($records),
        'locked' => $locked,
    ]));
}
$review = [];
foreach ($records as $record) {
    $item = $usage[$record->id];
    $types = [];
    if ($item['assignments']) {
        $types[] = get_string('usageassignments', 'local_activityicons', count($item['assignments']));
    }
    if ($item['types']) {
        $types[] = get_string('usagemappings', 'local_activityicons', implode(', ', $item['types']));
    }
    if ($item['fallback']) {
        $types[] = get_string('usagefallback', 'local_activityicons');
    }
    $review[] = html_writer::tag('strong', s($record->label)) . ' ' . s(implode(' · ', $types ?:
        [get_string('usagenone', 'local_activityicons')]));
}
echo html_writer::alist($review, ['class' => 'mb-3']);
$form->display();
echo \local_activityicons\local\context_help::render();
echo $OUTPUT->footer();
