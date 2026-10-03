<?php
namespace local_activityicons\form;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->libdir . '/formslib.php');

/** Confirmation form for deleting a selection of custom icons. */
final class delete_icons extends \moodleform {
    protected function definition(): void {
        $mform = $this->_form;
        $mform->addElement('hidden', 'ids', $this->_customdata['ids'] ?? '');
        $mform->setType('ids', PARAM_SEQUENCE);
        $this->add_action_buttons(true, get_string('delete'));
    }
}
