<?php
require('../../config.php');

$courseid = required_param('id', PARAM_INT);
$course = get_course($courseid);
require_login($course);
$context = context_course::instance($courseid);
require_capability('local/activityicons:manage', $context);
$url = new moodle_url('/local/activityicons/reset.php', ['id' => $courseid]);
$returnurl = new moodle_url('/local/activityicons/course.php', ['id' => $courseid]);
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_course($course);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('resetall', 'local_activityicons'));
$PAGE->set_heading($course->fullname);
$repository = new \local_activityicons\local\repository();
if (data_submitted() && optional_param('confirm', false, PARAM_BOOL)) {
    require_sesskey();
    $repository->reset_course($courseid);
    redirect($returnurl, get_string('resetdone', 'local_activityicons'), null,
        \core\output\notification::NOTIFY_SUCCESS);
}
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('resetall', 'local_activityicons'));
$confirm = new single_button(new moodle_url($url, ['confirm' => 1]),
    get_string('resetall', 'local_activityicons'), 'post');
echo $OUTPUT->confirm(get_string('resetconfirm', 'local_activityicons'), $confirm, $returnurl);
echo $OUTPUT->footer();
