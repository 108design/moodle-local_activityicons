<?php
/**
 * This file is part of a 108design source-available software product.
 *
 * Use and modification are permitted only under the Software License included
 * with this distribution. See LICENSE.md for the full terms.
 *
 * File documentation for classes/form/pool_icon.php.
 *
 * @package   local_activityicons
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license   See LICENSE.md for the full terms.
 */
namespace local_activityicons\form;
defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->libdir . '/formslib.php');

/** Additive batch upload: removing a draft never deletes an existing pool asset. */
final class pool_icon extends \moodleform {
    protected function definition(): void {
        $mform = $this->_form;
        $mform->addElement('header', 'uploadheader', get_string('batchupload', 'local_activityicons'));
        $mform->addElement('static', 'uploadnotice', '', get_string('batchhelp', 'local_activityicons'));
        $mform->addElement('filemanager', 'iconfiles', get_string('iconfiles', 'local_activityicons'), null, [
            'maxbytes' => \local_activityicons\local\image_validator::MAX_BYTES,
            'maxfiles' => 50, 'subdirs' => 0, 'accepted_types' => ['.svg', '.png', '.webp'],
        ]);
        $mform->addHelpButton('iconfiles', 'iconfiles', 'local_activityicons');
        $mform->addElement('static', 'iconfileslinkedhelp', '', \html_writer::span(\local_activityicons\local\context_help::icon('pool'), '', ['data-activityicons-help-field' => 'iconfiles']));
        $mform->addRule('iconfiles', null, 'required');
        $mform->addElement('submit', 'uploadiconsubmit', get_string('batchupload', 'local_activityicons'));
    }

    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        if (!$errors) {
            try {
                \local_activityicons\local\administration::validated_draft((int) $data['iconfiles']);
            } catch (\invalid_parameter_exception $exception) {
                $errors['iconfiles'] = s($exception->debuginfo);
            }
        }
        return $errors;
    }
}
