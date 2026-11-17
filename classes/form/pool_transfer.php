<?php
// This file is part of Moodle - https://moodle.org/
/**
 * This file is part of a 108design source-available software product.
 *
 * Use and modification are permitted only under the Software License included
 * with this distribution. See LICENSE.md for the full terms.
 *
 * File documentation for classes/form/pool_transfer.php.
 *
 * @package   local_activityicons
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license   See LICENSE.md for the full terms.
 */
namespace local_activityicons\form;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->libdir . '/formslib.php');

/** Collect a destination for a selection of stable icon keys. */
final class pool_transfer extends \moodleform {
    protected function definition(): void {
        $mform = $this->_form;
        $mform->addElement('hidden', 'selection', $this->_customdata['selection']);
        $mform->setType('selection', PARAM_RAW_TRIMMED);
        $mform->addElement('select', 'destination', get_string('destinationpool', 'local_activityicons'),
            ['' => get_string('choosedots')] + array_map('s', $this->_customdata['destinations']));
        $mform->setType('destination', PARAM_RAW_TRIMMED);
        $this->add_action_buttons(true, $this->_customdata['label']);
    }

    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        if (!array_key_exists($data['destination'] ?? '', $this->_customdata['destinations'])) {
            $errors['destination'] = get_string('required');
        }
        return $errors;
    }
}
