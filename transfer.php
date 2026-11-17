<?php
// This file is part of Moodle - https://moodle.org/
/**
 * This file is part of a 108design source-available software product.
 *
 * Use and modification are permitted only under the Software License included
 * with this distribution. See LICENSE.md for the full terms.
 *
 * File documentation for transfer.php.
 *
 * @package   local_activityicons
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license   See LICENSE.md for the full terms.
 */

require('../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use local_activityicons\form\pool_transfer;
use local_activityicons\local\admin_page;
use local_activityicons\local\administration;
use local_activityicons\local\context_help;
use local_activityicons\local\pool_browser;
use local_activityicons\local\pool_groups;

require_login();
require_capability('local/activityicons:managepool', context_system::instance());
if (!data_submitted()) {
    redirect(pool_browser::url());
}
require_sesskey();
$source = required_param('source', PARAM_ALPHANUMEXT);
$action = required_param('action', PARAM_ALPHA);
$url = new moodle_url('/local/activityicons/transfer.php', ['source' => $source, 'action' => $action]);
admin_page::prepare($url);
$returnurl = pool_browser::url($source);
$view = new pool_browser($source, $PAGE->get_renderer('core'));
$selection = optional_param('selection', '', PARAM_RAW_TRIMMED);
$keys = $selection === '' ? optional_param_array('icons', [], PARAM_ALPHANUMEXT) : explode(',', $selection);
$entries = $view->selection($keys, $action);
$ids = array_map(static fn($entry) => $entry['upload']->id ?? null, $entries);

switch ($action) {
    case 'delete':
        redirect(new moodle_url('/local/activityicons/delete.php', [
            'ids' => implode(',', $ids), 'returnpool' => $source,
        ]));
        break;
    case 'rename':
        $labels = optional_param_array('labels', [], PARAM_TEXT);
        $changes = [];
        foreach ($ids as $key => $id) {
            $changes[$id] = $labels[$key] ?? '';
        }
        administration::save_labels($changes);
        redirect($returnurl, get_string('adminsaved', 'local_activityicons'), null, \core\output\notification::NOTIFY_SUCCESS);
        break;
    case 'remove':
        pool_groups::remove_copies(array_keys($entries), $view->groupid);
        redirect($returnurl, get_string('removedfrompool', 'local_activityicons'), null,
            \core\output\notification::NOTIFY_SUCCESS);
        break;
}

$label = get_string($action . 'selectedicons', 'local_activityicons');
$form = new pool_transfer($url, [
    'selection' => implode(',', array_keys($entries)), 'label' => $label, 'destinations' => $view->destinations($action),
]);
if ($form->is_cancelled()) {
    redirect($returnurl);
}
if ($data = $form->get_data()) {
    $destination = (int) $data->destination;
    if ($action === 'copy') {
        pool_groups::copy_icons(array_keys($entries), $destination);
    } else {
        pool_groups::move_uploads(array_values($ids), $destination, $view->groupid);
    }
    redirect($returnurl, get_string($action === 'copy' ? 'iconscopied' : 'iconsmoved', 'local_activityicons'), null,
        \core\output\notification::NOTIFY_SUCCESS);
}
echo $OUTPUT->header();
echo admin_page::tabs('pool');
echo $OUTPUT->heading($label, 2);
echo html_writer::tag('p', get_string($action . 'transferintro', 'local_activityicons') .
    context_help::icon('pool'), ['class' => 'mb-3']);
echo html_writer::alist(array_map(static fn($entry) => s($entry['label']), $entries), ['class' => 'mb-4']);
$form->display();
echo context_help::render();
echo $OUTPUT->footer();
