<?php
// This file is part of Moodle - https://moodle.org/
/**
 * This file is part of a 108design source-available software product.
 *
 * Use and modification are permitted only under the Software License included
 * with this distribution. See LICENSE.md for the full terms.
 *
 * File documentation for course.php.
 *
 * @package   local_activityicons
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license   See LICENSE.md for the full terms.
 */
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
\local_activityicons\local\context_help::prepare();
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('coursemanage', 'local_activityicons'));
echo html_writer::tag('p', get_string('coursemanage_desc', 'local_activityicons'));
$form->display();
if (has_capability('moodle/site:config', context_system::instance())) {
    $component = 'local_activityicons';
    echo html_writer::start_tag('details', ['class' => 'card mt-4 mb-4 local-activityicons-course-admin']);
    echo html_writer::tag('summary', get_string('courseadmin', $component), ['class' => 'card-header fw-semibold font-weight-bold']);
    echo html_writer::start_div('card-body');
    if (has_capability('local/activityicons:managepool', context_system::instance())) {
        echo html_writer::div(html_writer::link(new moodle_url('/local/activityicons/pool.php'),
            get_string('adminlink', $component), ['class' => 'btn btn-secondary']), 'mb-4');
    }
    echo $OUTPUT->heading(get_string('resetall', $component), 3, 'h5');
    echo html_writer::tag('p', get_string('resetall_desc', $component) . ' ' .
        \local_activityicons\local\context_help::icon('courseadmin'), ['class' => 'mb-3']);
    echo html_writer::link(new moodle_url('/local/activityicons/reset.php', ['id' => $courseid]),
        get_string('resetall', $component), ['class' => 'btn btn-outline-danger local-activityicons-quiet-danger']);
    echo html_writer::end_div();
    echo html_writer::end_tag('details');
}
echo \local_activityicons\local\context_help::render();
echo $OUTPUT->footer();
