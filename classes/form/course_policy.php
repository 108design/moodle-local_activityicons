<?php
/**
 * This file is part of a 108design source-available software product.
 *
 * Use and modification are permitted only under the Software License included
 * with this distribution. See LICENSE.md for the full terms.
 *
 * File documentation for classes/form/course_policy.php.
 *
 * @package   local_activityicons
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license   See LICENSE.md for the full terms.
 */
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
