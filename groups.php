<?php
// This file is part of Moodle - https://moodle.org/
/**
 * This file is part of a 108design source-available software product.
 *
 * Use and modification are permitted only under the Software License included
 * with this distribution. See LICENSE.md for the full terms.
 *
 * File documentation for groups.php.
 *
 * @package   local_activityicons
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license   See LICENSE.md for the full terms.
 */
require('../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use local_activityicons\form\group_edit;
use local_activityicons\local\admin_page;
use local_activityicons\local\context_help;
use local_activityicons\local\pool_groups;

require_login();
require_capability('local/activityicons:managepool', context_system::instance());

$overview = new moodle_url('/local/activityicons/pool.php');
$url = new moodle_url('/local/activityicons/groups.php');
$action = optional_param('action', '', PARAM_ALPHA);
$id = optional_param('id', 0, PARAM_INT);
if ($action === '') {
    redirect($overview);
}
$group = pool_groups::records()[$id] ?? null;
if ((!$group && ($id || $action !== 'edit')) || !in_array($action, ['edit', 'reorder', 'delete', 'confirmdelete', 'contents'], true)) {
    throw new moodle_exception('groupmissing', 'local_activityicons', $overview);
}
if ($action === 'contents') {
    redirect(new moodle_url($overview, ['pool' => pool_groups::picker_key($id)]));
}
if ($action === 'reorder' || $action === 'delete') {
    if (!data_submitted()) {
        throw new moodle_exception('invalidaccess');
    }
    require_sesskey();
    if ($action === 'reorder') {
        pool_groups::reorder($id, required_param('direction', PARAM_INT));
        redirect($overview);
    }
    pool_groups::delete($id);
    redirect($overview, get_string('groupdeleted', 'local_activityicons'), null, \core\output\notification::NOTIFY_SUCCESS);
}

$url->params(['action' => $action, 'id' => $id]);
admin_page::prepare($url);
if ($action === 'edit') {
    $entries = $group ? (new \local_activityicons\local\pool_browser(
        pool_groups::picker_key($id),
        $PAGE->get_renderer('core')
    ))->entries : [];
    $form = new group_edit($url, ['id' => $id, 'entries' => $entries]);
    if ($form->is_cancelled()) {
        redirect($overview);
    }
    if ($data = $form->get_data()) {
        pool_groups::save($id, $data->groupname, reference: $data->referenceicon ?? null);
        redirect($overview, get_string('groupsaved', 'local_activityicons'), null, \core\output\notification::NOTIFY_SUCCESS);
    }
    if ($group) {
        $reference = $group->referenceicon ?? '';
        $form->set_data(['groupname' => $group->name, 'referenceicon' => isset($entries[$reference]) ? $reference : 'auto']);
    }
}

echo $OUTPUT->header();
echo admin_page::tabs('pool');
if ($action === 'edit') {
    echo $OUTPUT->heading(get_string($id ? 'poolmetadata' : 'addgroup', 'local_activityicons'), 2);
    $form->display();
} else {
    $question = get_string('groupdeleteconfirm', 'local_activityicons', s($group->name));
    $confirm = new single_button(new moodle_url($url, ['action' => 'delete']), get_string('delete'), 'post');
    echo $OUTPUT->confirm($question, $confirm, $overview);
}
echo context_help::render();
echo $OUTPUT->footer();
