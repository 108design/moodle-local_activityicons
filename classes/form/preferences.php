<?php
// This file is part of Moodle - https://moodle.org/
/**
 * This file is part of a 108design source-available software product.
 *
 * Use and modification are permitted only under the Software License included
 * with this distribution. See LICENSE.md for the full terms.
 *
 * File documentation for classes/form/preferences.php.
 *
 * @package   local_activityicons
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license   See LICENSE.md for the full terms.
 */
namespace local_activityicons\form;

use local_activityicons\local\context_help;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->libdir . '/formslib.php');

/** Administrator-only behaviour settings. */
final class preferences extends \moodleform {
    protected function definition(): void {
        $mform = $this->_form;
        $mform->addElement('advcheckbox', 'autoh5p', get_string('autoh5p', 'local_activityicons'));
        $mform->addHelpButton('autoh5p', 'autoh5p', 'local_activityicons');
        $mform->addElement('static', 'autoh5plinkedhelp', '', \html_writer::span(
            context_help::icon('behaviour'), '', ['data-activityicons-help-field' => 'autoh5p']
        ));
        $mform->addElement('advcheckbox', 'dynamicfallback', get_string('dynamicfallback', 'local_activityicons'));
        $mform->addHelpButton('dynamicfallback', 'dynamicfallback', 'local_activityicons');
        $mform->addElement('static', 'dynamicfallbacklinkedhelp', '', \html_writer::span(
            context_help::icon('behaviour'), '', ['data-activityicons-help-field' => 'dynamicfallback']
        ));
        $mform->addElement('static', 'developerwarning', '', \html_writer::div(
            get_string('developermodewarning', 'local_activityicons'), 'alert alert-warning'
        ));
        $mform->addElement('advcheckbox', 'developermode', get_string('developermodeenable', 'local_activityicons'));
        $mform->addHelpButton('developermode', 'developermodeenable', 'local_activityicons');
        $mform->addElement('static', 'developermodelinkedhelp', '', \html_writer::span(
            context_help::icon('developer'), '', ['data-activityicons-help-field' => 'developermode']
        ));
        $this->add_action_buttons(false);
    }
}
