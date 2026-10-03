<?php
namespace local_activityicons\form;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->libdir . '/formslib.php');

/** Standard Moodle form for the course policy. */
final class course_policy extends \moodleform {
    protected function definition(): void {
        $mform = $this->_form;
        $mform->addElement('hidden', 'id', $this->_customdata['courseid']);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('header', 'policy', get_string('courseautoh5p', 'local_activityicons'));
        $mform->addElement('select', 'autoh5pmode', get_string('courseautoh5p', 'local_activityicons'), [
            'inherit' => get_string('courseautoinherit', 'local_activityicons'),
            'enabled' => get_string('courseautoenabled', 'local_activityicons'),
            'disabled' => get_string('courseautodisabled', 'local_activityicons'),
        ]);
        $mform->addHelpButton('autoh5pmode', 'courseautoh5p', 'local_activityicons');
        $this->add_action_buttons();
    }
}
