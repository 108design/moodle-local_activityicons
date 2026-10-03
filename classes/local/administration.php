<?php
namespace local_activityicons\local;

/** Shared validation/persistence for the unified Moodle administration page. */
final class administration {
    public static function defaults(): \stdClass {
        $data = new \stdClass();
        foreach (['autoh5p' => 0, 'dynamicfallback' => 1, 'h5pfallback' => 'h5p',
                'h5pmapping' => h5p_resolver::default_mapping_text()] as $name => $default) {
            $value = get_config('local_activityicons', $name);
            $data->$name = $value === false ? $default : $value;
        }
        $data->labels = [];
        foreach (pool::records() as $record) {
            $data->labels[$record->id] = $record->label;
        }
        $data->machine = [];
        $data->mappingicon = [];
        foreach (h5p_resolver::parse_mapping($data->h5pmapping) as $machine => $key) {
            $data->machine[] = 'H5P.' . $machine;
            $data->mappingicon[] = $key;
        }
        unset($data->h5pmapping);
        return $data;
    }

    /** Form fields are bound to stable internal identities; people edit only H5P type names. */
    public static function mapping_input(\stdClass $data): string {
        if (isset($data->h5pmapping)) {
            return (string) $data->h5pmapping; // Retain the low-level configuration boundary.
        }
        $lines = [];
        foreach ((array) ($data->machine ?? []) as $index => $machine) {
            if (!empty($data->mappingdeleted[$index]) || !empty($data->{'removemapping-hidden'}[$index])) {
                continue;
            }
            $machine = trim($machine);
            $key = $data->mappingicon[$index] ?? '';
            if ($machine === '' && $key === '') {
                continue;
            }
            icon_catalog::normalise($key);
            $lines[] = $machine . '=' . $key;
        }
        return implode("\n", $lines);
    }

    /** Installed runnable libraries plus saved/default types; versions share one choice. */
    public static function machine_options(): array {
        global $DB;
        $options = ['' => get_string('chooseh5ptype', 'local_activityicons')];
        $known = array_merge(h5p_resolver::parse_mapping(h5p_resolver::default_mapping_text()),
            h5p_resolver::configured_mapping());
        foreach ($known as $machine => $key) {
            $options['H5P.' . $machine] = 'H5P.' . $machine;
        }
        foreach ($DB->get_records('h5p_libraries', ['runnable' => 1], 'title, id', 'id, machinename, title') as $library) {
            $machine = strtolower(preg_replace('/^H5P\./i', '', $library->machinename));
            $options['H5P.' . $machine] = $library->title . ' (' . $library->machinename . ')';
        }
        if ($DB->get_manager()->table_exists(new \xmldb_table('hvp_libraries'))) {
            $fields = 'id, machine_name, title';
            foreach ($DB->get_records('hvp_libraries', ['runnable' => 1], 'title, id', $fields) as $library) {
                $machine = strtolower(preg_replace('/^H5P\./i', '', $library->machine_name));
                $key = 'H5P.' . $machine;
                if (!isset($options[$key])) {
                    $options[$key] = $library->title . ' (' . $library->machine_name . ')';
                }
            }
        }
        return $options;
    }

    public static function save(\stdClass $data): void {
        require_capability('local/activityicons:managepool', \context_system::instance());
        $lock = \core\lock\lock_config::get_lock_factory('local_activityicons')->get_lock('poolmetadata', 10);
        if (!$lock) {
            throw new \moodle_exception('locktimeout', 'error');
        }
        try {
            pool::reset_cache();
            self::save_locked($data);
        } finally {
            $lock->release();
        }
    }

    private static function save_locked(\stdClass $data): void {
        global $DB;
        $mapping = self::mapping_input($data);
        $error = h5p_resolver::validate_mapping($mapping);
        if ($error !== '') {
            throw new \invalid_parameter_exception($error);
        }
        if ($data->h5pfallback !== 'default') {
            icon_catalog::normalise($data->h5pfallback);
        }
        $records = [];
        foreach (pool::records() as $record) {
            $records[$record->id] = $record;
        }
        $labels = [];
        foreach ((array) ($data->labels ?? []) as $id => $label) {
            $label = trim(clean_param($label, PARAM_TEXT));
            if (!isset($records[$id]) || $label === '' || \core_text::strlen($label) > 100) {
                throw new \invalid_parameter_exception(get_string('invalidlabel', 'local_activityicons'));
            }
            $labels[$id] = $label;
        }
        $transaction = $DB->start_delegated_transaction();
        foreach ($labels as $id => $label) {
            if ($records[$id]->label !== $label) {
                $DB->set_field(pool::TABLE, 'label', $label, ['id' => $id]);
            }
        }
        set_config('h5pmapping', $mapping, 'local_activityicons');
        set_config('h5pfallback', $data->h5pfallback, 'local_activityicons');
        // General site behaviour remains restricted to site administrators.
        if (has_capability('moodle/site:config', \context_system::instance())) {
            set_config('autoh5p', empty($data->autoh5p) ? 0 : 1, 'local_activityicons');
            set_config('dynamicfallback', empty($data->dynamicfallback) ? 0 : 1, 'local_activityicons');
        }
        $transaction->allow_commit();
        pool::reset_cache();
    }

    /** Read only the current user's draft and validate the complete batch before any pool write. */
    public static function validated_draft(int $draftid): array {
        global $USER;
        require_capability('local/activityicons:managepool', \context_system::instance());
        $files = get_file_storage()->get_area_files(\context_user::instance($USER->id)->id,
            'user', 'draft', $draftid, 'filename', false);
        if (!$draftid || !$files || count($files) > 50) {
            throw new \invalid_parameter_exception(get_string('batchrequired', 'local_activityicons'));
        }
        foreach ($files as $file) {
            try {
                if ($file->get_filepath() !== '/' || $file->get_filesize() > image_validator::MAX_BYTES) {
                    throw new \invalid_parameter_exception('Invalid size/path');
                }
                image_validator::sanitise($file->get_filename(), $file->get_content());
            } catch (\invalid_parameter_exception $exception) {
                throw new \invalid_parameter_exception(get_string('batchinvalid', 'local_activityicons',
                    $file->get_filename()));
            }
        }
        return $files;
    }

    public static function import_draft(int $draftid): int {
        global $DB;
        $files = self::validated_draft($draftid);
        $transaction = $DB->start_delegated_transaction();
        $keys = [];
        foreach ($files as $file) {
            $label = trim(clean_param(pathinfo($file->get_filename(), PATHINFO_FILENAME), PARAM_TEXT));
            $label = \core_text::substr($label, 0, 100);
            $record = pool::store($label !== '' ? $label : get_string('activityicon', 'local_activityicons'),
                $file->get_filename(), $file->get_content());
            $keys[$record->iconkey] = true;
        }
        $transaction->allow_commit();
        return count($keys);
    }
}
