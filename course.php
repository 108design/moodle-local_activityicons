<?php
require('../../config.php');

$courseid = required_param('id', PARAM_INT);
$course = get_course($courseid);
require_login($course);
$context = context_course::instance($courseid);
require_capability('local/activityicons:manage', $context);
$url = new moodle_url('/local/activityicons/course.php', ['id' => $courseid]);
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_course($course);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('coursemanage', 'local_activityicons'));
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add(get_string('coursemanage', 'local_activityicons'));
$repository = new \local_activityicons\local\repository();
$form = new \local_activityicons\form\course_policy(null, ['courseid' => $courseid]);
if ($form->is_cancelled()) {
    redirect(new moodle_url('/course/view.php', ['id' => $courseid]));
}
if ($data = $form->get_data()) {
    $repository->set_course_auto_mode($courseid, $data->autoh5pmode);
    redirect($url, get_string('coursepolicysaved', 'local_activityicons'), null,
        \core\output\notification::NOTIFY_SUCCESS);
}
$form->set_data(['autoh5pmode' => $repository->get_course_auto_mode($courseid)]);
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('coursemanage', 'local_activityicons'));
echo html_writer::tag('p', get_string('coursemanage_desc', 'local_activityicons'));
$form->display();
if (has_capability('local/activityicons:managepool', context_system::instance())) {
    echo html_writer::tag('p', html_writer::link(new moodle_url('/local/activityicons/pool.php'),
        get_string('adminlink', 'local_activityicons')));
}
echo $OUTPUT->heading(get_string('resetall', 'local_activityicons'), 3);
echo html_writer::tag('p', get_string('resetall_desc', 'local_activityicons'));
echo html_writer::link(new moodle_url('/local/activityicons/reset.php', ['id' => $courseid]),
    get_string('resetall', 'local_activityicons'), ['class' => 'btn btn-outline-danger']);
echo $OUTPUT->footer();
