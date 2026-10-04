<?php
namespace local_activityicons\form;
defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->libdir . '/formslib.php');

/** Native collapsible administration without user-facing internal asset keys. */
final class administration extends \moodleform {
    protected function definition(): void {
        global $PAGE;
        $mform = $this->_form;
        $entries = \local_activityicons\local\icon_catalog::client_entries($PAGE->get_renderer('core'));
        $custom = \local_activityicons\local\pool::records();
        $used = \local_activityicons\local\pool::used_keys();
        $mform->addElement('header', 'poolheader', get_string('poollabels', 'local_activityicons'));
        $mform->addElement('static', 'labelsnotice', '', get_string('labelshelp', 'local_activityicons'));
        $mform->addElement('html', \html_writer::start_div('local-activityicons-pool-grid mb-3'));
        foreach ($entries as $entry) {
            $mform->addElement('html', \html_writer::start_div('local-activityicons-pool-card'));
            if (!isset($custom[$entry['key']])) {
                $mform->addElement('html', self::preview($entry) .
                    \html_writer::span(s($entry['label']), 'local-activityicons-pool-label'));
                $mform->addElement('html', \html_writer::end_div());
                continue;
            }
            $record = $custom[$entry['key']];
            $name = 'labels[' . $record->id . ']';
            $delete = 'deleteicons[' . $record->id . ']';
            $inuse = isset($used[$record->iconkey]);
            $group = [
                $mform->createElement('static', 'preview' . $record->id, '', self::preview($entry)),
                $mform->createElement('text', $name, '', ['maxlength' => 100, 'size' => 12,
                    'class' => 'local-activityicons-label-input',
                    'aria-label' => get_string('iconlabel', 'local_activityicons') . ': ' . $record->label]),
            ];
            if ($inuse) {
                $tooltip = get_string('iconinuse_batch', 'local_activityicons');
                $checkbox = \html_writer::empty_tag('input', [
                    'type' => 'checkbox', 'name' => $delete, 'value' => 1,
                    'class' => 'local-activityicons-pool-delete', 'disabled' => 'disabled',
                    'tabindex' => -1, 'aria-hidden' => 'true',
                ]);
                $lockedcontrol = \html_writer::span($checkbox, 'local-activityicons-pool-delete-tooltip', [
                    'role' => 'checkbox', 'aria-checked' => 'false', 'aria-disabled' => 'true',
                    'aria-label' => $tooltip . ': ' . $record->label,
                    'tabindex' => 0, 'title' => $tooltip,
                    'data-toggle' => 'tooltip', 'data-placement' => 'top',
                ]);
                $group[] = $mform->createElement('static', 'deletelocked' . $record->id, '', $lockedcontrol);
            } else {
                $group[] = $mform->createElement('checkbox', $delete, '', '', [
                    'class' => 'local-activityicons-pool-delete',
                    'aria-label' => get_string('selecticondelete', 'local_activityicons', $record->label),
                    'title' => get_string('selecticondelete', 'local_activityicons', $record->label),
                ]);
            }
            $mform->addGroup($group, 'customicon' . $record->id, '', '', false);
            $mform->setType($name, PARAM_TEXT);
            if (!$inuse) {
                $mform->setType($delete, PARAM_BOOL);
            }
            $mform->addGroupRule('customicon' . $record->id,
                [$name => [[get_string('required'), 'required', null, 'client']]]);
            $mform->addElement('html', \html_writer::end_div());
        }
        $mform->addElement('html', \html_writer::end_div());
        if ($custom) {
            $mform->addElement('submit', 'deleteiconsubmit', get_string('deleteiconsselected', 'local_activityicons'), [
                'formaction' => (new \moodle_url('/local/activityicons/delete.php'))->out(false),
                'formmethod' => 'post',
                'formnovalidate' => 'formnovalidate',
                'data-skip-validation' => 1,
            ]);
            $mform->registerNoSubmitButton('deleteiconsubmit');
        }
        $mform->addElement('header', 'mappingheader', get_string('h5pmapping', 'local_activityicons'));
        $mform->addElement('static', 'mappingnotice', '', get_string('mappinghelp', 'local_activityicons'));
        $defaults = \local_activityicons\local\administration::defaults();
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
                ['' => get_string('chooseicon', 'local_activityicons')] +
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
        if ($adding || $toggled !== null) {
            $mform->setExpanded('mappingheader', true);
        }
        $mform->addElement('html', \html_writer::end_div());
        $mform->addElement('select', 'h5pfallback', get_string('h5pfallback', 'local_activityicons'),
            ['default' => get_string('h5pfallbackdefault', 'local_activityicons')] +
            \local_activityicons\local\icon_catalog::key_options());
        $mform->addElement('static', 'fallbacknotice', '', get_string('h5pfallback_desc', 'local_activityicons'));
        if (has_capability('moodle/site:config', \context_system::instance())) {
            $mform->addElement('header', 'behaviourheader', get_string('sitebehaviour', 'local_activityicons'));
            $mform->addElement('advcheckbox', 'autoh5p', get_string('autoh5p', 'local_activityicons'));
            $mform->addElement('static', 'autonotice', '', get_string('autoh5p_desc', 'local_activityicons'));
            $mform->addElement('advcheckbox', 'dynamicfallback', get_string('dynamicfallback', 'local_activityicons'));
            $mform->addElement('static', 'dynamicnotice', '', get_string('dynamichelp', 'local_activityicons'));
        }
        $this->add_action_buttons(false);
    }

    private static function preview(array $entry): string {
        return \html_writer::empty_tag('img', ['src' => $entry['url'], 'alt' => '',
            'width' => 32, 'height' => 32, 'class' => 'local-activityicons-preview']);
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
        foreach ($data['labels'] ?? [] as $id => $label) {
            if (trim($label) === '' || \core_text::strlen($label) > 100) {
                $errors['customicon' . $id] = get_string('invalidlabel', 'local_activityicons');
            }
        }
        return $errors;
    }
}
