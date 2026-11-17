<?php
// This file is part of Moodle - https://moodle.org/
/**
 * This file is part of a 108design source-available software product.
 *
 * Use and modification are permitted only under the Software License included
 * with this distribution. See LICENSE.md for the full terms.
 *
 * File documentation for classes/form/h5p_mapping.php.
 *
 * @package   local_activityicons
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license   See LICENSE.md for the full terms.
 */
namespace local_activityicons\form;
defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->libdir . '/formslib.php');

/** Edit the H5P type rules and fallback icon. */
final class h5p_mapping extends \moodleform {
    protected function definition(): void {
        $mform = $this->_form;
        $defaults = \local_activityicons\local\administration::mapping_defaults();
        $count = $this->optional_param('mappingcount', count($defaults->machine), PARAM_INT);
        if ($count < 0 || $count > 500) {
            throw new \invalid_parameter_exception('Too many mapping rows');
        }
        $adding = $this->optional_param('addmapping', '', PARAM_TEXT) !== '';
        $pending = [];
        $empty = [];
        $pruned = [];
        $deleted = [];
        $toggled = null;
        for ($index = 0; $index < $count; $index++) {
            $deleted[$index] = $this->optional_param("mappingdeleted[$index]", 0, PARAM_BOOL);
            if ($this->optional_param("removemapping[$index]", false, PARAM_RAW)) {
                $deleted[$index] = !$deleted[$index];
                $toggled = $index;
            }
            if ($this->optional_param("removemapping-hidden[$index]", false, PARAM_RAW)) {
                $pruned[] = $index;
                continue;
            }
            if ($deleted[$index]) {
                continue;
            }
            $machine = $this->optional_param("machine[$index]", $defaults->machine[$index] ?? '', PARAM_RAW);
            $icon = $this->optional_param("mappingicon[$index]", $defaults->mappingicon[$index] ?? '', PARAM_RAW);
            if ($machine === '' || $icon === '') {
                $pending[] = $index;
                if ($machine === '' && $icon === '') {
                    $empty[] = $index;
                }
            }
        }
        // Collapse old blank slots, but never discard partially entered user data.
        $drop = array_diff($pending, $empty) ? $empty : array_slice($empty, 1);
        $drop = array_merge($drop, $pruned);
        $pending = array_values(array_diff($pending, $drop));
        $increment = $pending || $count === 500 ? 0 : 1;
        $focus = $pending[0] ?? min($count, 499);
        $mform->addElement('html', \html_writer::start_div('local-activityicons-mappings mb-3',
            ['data-focus-row' => $adding ? $focus : '', 'data-toggle-row' => $toggled ?? '']));
        $row = $mform->createElement('group', 'mappingrow', '', [
            $mform->createElement('select', 'machine', get_string('h5ptypes', 'local_activityicons'),
                \local_activityicons\local\administration::machine_options(),
                ['aria-label' => get_string('chooseh5ptype', 'local_activityicons')]),
            $mform->createElement('select', 'mappingicon', get_string('activityicon', 'local_activityicons'),
                ['' => get_string('chooseicon', 'local_activityicons'),
                    'default' => get_string('h5pfallbackdefault', 'local_activityicons')] +
                \local_activityicons\local\icon_catalog::key_options(), ['class' => 'local-activityicons-mapping-select']),
            $mform->createElement('submit', 'removemapping', '×',
                ['data-skip-validation' => 1, 'data-no-submit' => 1,
                    'aria-label' => get_string('removemapping', 'local_activityicons'),
                    'title' => get_string('removemapping', 'local_activityicons')]),
        ], '', false);
        $repeats = $this->repeat_elements([$row, $mform->createElement('hidden', 'mappingdeleted', 0)],
            count($defaults->machine), ['machine' => ['type' => PARAM_RAW], 'mappingicon' => ['type' => PARAM_RAW],
                'mappingdeleted' => ['type' => PARAM_BOOL]],
            'mappingcount', 'addmapping', $increment, get_string('addmapping', 'local_activityicons'), true);
        for ($index = 0; $index < $repeats; $index++) {
            $mform->registerNoSubmitButton("removemapping[$index]");
            $mform->setConstants(['mappingdeleted' => [$index => (int) ($deleted[$index] ?? 0)]]);
            if (!empty($deleted[$index])) {
                foreach ($mform->getElement("mappingrow[$index]")->getElements() as $element) {
                    if ($element->getName() === "removemapping[$index]") {
                        $element->setValue('↶');
                        $element->updateAttributes(['aria-label' => get_string('undoremovemapping', 'local_activityicons'),
                            'title' => get_string('undoremovemapping', 'local_activityicons')]);
                    }
                }
            }
        }
        foreach ($drop as $index) {
            $mform->removeElement("mappingrow[$index]");
            $mform->addElement('hidden', "removemapping-hidden[$index]", 1);
            $mform->setType("removemapping-hidden[$index]", PARAM_INT);
            $mform->setConstants(['removemapping-hidden' => [$index => 1]]);
        }
        $mform->addElement('html', \html_writer::end_div());
        $mform->addElement('select', 'h5pfallback', get_string('h5pfallback', 'local_activityicons'),
            ['default' => get_string('h5pfallbackdefault', 'local_activityicons')] +
            \local_activityicons\local\icon_catalog::key_options());
        $mform->addHelpButton('h5pfallback', 'h5pfallback', 'local_activityicons');
        $mform->addElement('static', 'h5pfallbacklinkedhelp', '', \html_writer::span(\local_activityicons\local\context_help::icon('mapping'), '', ['data-activityicons-help-field' => 'h5pfallback']));
        $this->add_action_buttons(false);
    }

    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        try {
            $mapping = \local_activityicons\local\administration::mapping_input((object) $data);
            $error = \local_activityicons\local\h5p_resolver::validate_mapping($mapping);
        } catch (\invalid_parameter_exception $exception) {
            $error = get_string('invalidselection', 'local_activityicons');
        }
        if ($error !== '') {
            // Place errors inside the mapping region so Moodle expands it automatically.
            $index = array_key_first($data['machine'] ?? []);
            $errors['mappingrow[' . $index . ']'] = $error;
        }
        return $errors;
    }
}
