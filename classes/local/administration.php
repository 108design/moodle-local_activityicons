<?php
// This file is part of Moodle - https://moodle.org/
/**
 * This file is part of a 108design source-available software product.
 *
 * Use and modification are permitted only under the Software License included
 * with this distribution. See LICENSE.md for the full terms.
 *
 * File documentation for classes/local/administration.php.
 *
 * @package   local_activityicons
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license   See LICENSE.md for the full terms.
 */
namespace local_activityicons\local;

/** Shared validation/persistence for the Activity Icons administration tabs. */
final class administration {
    /** Current values for the H5P Mapping tab. */
    public static function mapping_defaults(): \stdClass {
        $fallback = get_config('local_activityicons', 'h5pfallback');
        $data = (object) ['h5pfallback' => $fallback === false ? 'h5p' : $fallback, 'machine' => [], 'mappingicon' => []];
        foreach (h5p_resolver::configured_mapping() as $machine => $key) {
            $data->machine[] = 'H5P.' . $machine;
            $data->mappingicon[] = $key;
        }
        return $data;
    }

    /** Current site switches, including the default for installations without stored values. */
    public static function setting_defaults(): \stdClass {
        $data = new \stdClass();
        foreach (['autoh5p' => 0, 'dynamicfallback' => 1, 'developermode' => 0] as $name => $default) {
            $value = get_config('local_activityicons', $name);
            $data->$name = $value === false ? $default : (int) $value;
        }
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
            if ($key !== repository::SELECTION_DEFAULT) {
                icon_catalog::normalise($key);
            }
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

    /** Save only the H5P rules and fallback. */
    public static function save_mapping(\stdClass $data): void {
        self::write(static function() use ($data): void {
            $mapping = self::mapping_input($data);
            $error = h5p_resolver::validate_mapping($mapping);
            if ($error !== '') {
                throw new \invalid_parameter_exception($error);
            }
            if ($data->h5pfallback !== repository::SELECTION_DEFAULT) {
                icon_catalog::normalise($data->h5pfallback);
            }
            set_config('h5pmapping', $mapping, 'local_activityicons');
            set_config('h5pfallback', $data->h5pfallback, 'local_activityicons');
        });
    }

    /** Update labels without submitting mappings or settings. */
    public static function save_labels(array $labels): void {
        self::write(static function() use ($labels): void {
            global $DB;
            $records = [];
            foreach (pool::records() as $record) {
                $records[$record->id] = $record;
            }
            $updates = [];
            foreach ((array) $labels as $id => $label) {
                $label = trim(clean_param($label, PARAM_TEXT));
                if (!isset($records[$id]) || $label === '' || \core_text::strlen($label) > 100) {
                    throw new \invalid_parameter_exception(get_string('invalidlabel', 'local_activityicons'));
                }
                $updates[$id] = $label;
            }
            foreach ($updates as $id => $label) {
                if ($records[$id]->label !== $label) {
                    $DB->set_field(pool::TABLE, 'label', $label, ['id' => $id]);
                }
            }
        });
    }

    /** Update site switches without rewriting stored mapping text. */
    public static function save_settings(\stdClass $data): void {
        require_capability('moodle/site:config', \context_system::instance());
        self::write(static function() use ($data): void {
            foreach (['autoh5p', 'dynamicfallback', 'developermode'] as $name) {
                set_config($name, !empty($data->$name) ? 1 : 0, 'local_activityicons');
            }
        });
    }

    /** Use the same lock as pool deletion and assignments. */
    private static function write(callable $callback): void {
        global $DB;
        require_capability('local/activityicons:managepool', \context_system::instance());
        $lock = pool::metadata_lock();
        try {
            pool::reset_cache();
            $transaction = $DB->start_delegated_transaction();
            try {
                $callback();
                $transaction->allow_commit();
            } catch (\Throwable $error) {
                $transaction->rollback($error);
            }
            pool::reset_cache();
        } finally {
            $lock->release();
        }
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

    /** Import the validated draft into the chosen group in one transaction. */
    public static function import_draft(int $draftid, int $groupid = 0): int {
        $files = self::validated_draft($draftid);
        $records = pool::store_batch(self::draft_entries($files), $groupid);
        return count(array_unique(array_column($records, 'iconkey')));
    }

    /** Yield one file's content at a time so the batch does not keep all raster data in memory. */
    private static function draft_entries(array $files): \Generator {
        foreach ($files as $file) {
            $label = trim(clean_param(pathinfo($file->get_filename(), PATHINFO_FILENAME), PARAM_TEXT));
            $label = \core_text::substr($label, 0, 100);
            yield [$label !== '' ? $label : get_string('activityicon', 'local_activityicons'),
                $file->get_filename(), $file->get_content()];
        }
    }
}
