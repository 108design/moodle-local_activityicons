<?php
// This file is part of Moodle - https://moodle.org/
/**
 * This file is part of a 108design source-available software product.
 *
 * Use and modification are permitted only under the Software License included
 * with this distribution. See LICENSE.md for the full terms.
 *
 * File documentation for classes/form/group_edit.php.
 *
 * @package   local_activityicons
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license   See LICENSE.md for the full terms.
 */
namespace local_activityicons\form;

use local_activityicons\local\pool_groups;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->libdir . '/formslib.php');

/** Create a pool or edit its display metadata. */
final class group_edit extends \moodleform {
    protected function definition(): void {
        $mform = $this->_form;
        $mform->addElement('text', 'groupname', get_string('groupname', 'local_activityicons'), ['maxlength' => 100]);
        $mform->setType('groupname', PARAM_TEXT);
        $mform->addRule('groupname', null, 'required', null, 'client');
        if ($this->_customdata['id']) {
            $entries = $this->_customdata['entries'];
            $options = ['auto' => get_string('poolreferenceauto', 'local_activityicons')];
            foreach ($entries as $key => $entry) {
                $options[$key] = s($entry['label']);
            }
            $mform->addElement('autocomplete', 'referenceicon', get_string('poolreference', 'local_activityicons'),
                $options, [
                    'placeholder' => get_string('searchicons', 'local_activityicons'),
                    'valuehtmlcallback' => static function(string $key) use ($entries) {
                        if (!isset($entries[$key])) {
                            return false;
                        }
                        $entry = $entries[$key];
                        return \html_writer::empty_tag('img', [
                            'src' => $entry['url'], 'width' => 24, 'height' => 24, 'alt' => '',
                            'class' => 'me-2 mr-2',
                        ]) . s($entry['label']);
                    },
                ]);
            $mform->setType('referenceicon', PARAM_ALPHANUMEXT);
            $mform->addHelpButton('referenceicon', 'poolreference', 'local_activityicons');
        }
        $this->add_action_buttons();
    }

    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        $error = pool_groups::name_error(trim($data['groupname'] ?? ''), $this->_customdata['id']);
        if ($error !== null) {
            $errors['groupname'] = $error;
        }
        return $errors;
    }
}
