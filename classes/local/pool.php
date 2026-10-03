<?php
namespace local_activityicons\local;

/** Shared, immutable decorative assets stored through Moodle's File API. */
final class pool {
    public const TABLE = 'local_activityicons_pool';
    public const FILEAREA = 'pool';

    public static function records(): array {
        global $DB;
        // Also called while Moodle discovers settings before the first upgrade.
        if ((int) get_config('local_activityicons', 'version') < 2026091003) {
            return [];
        }
        $cache = \cache::make_from_params(\cache_store::MODE_REQUEST, 'local_activityicons', 'pool');
        $cached = $cache->get('records');
        if ($cached !== false) {
            return $cached;
        }
        $result = [];
        foreach ($DB->get_records(self::TABLE, null, 'label, id') as $record) {
            $result[$record->iconkey] = $record;
        }
        $cache->set('records', $result);
        return $result;
    }

    /** Save validated bytes; content-derived keys make cross-site restore independent of local IDs. */
    public static function store(string $label, string $filename, string $bytes): \stdClass {
        global $DB;
        $image = image_validator::sanitise($filename, $bytes);
        $key = 'custom_' . substr(hash('sha256', $image['content']), 0, 40);
        $label = trim(clean_param($label, PARAM_TEXT));
        if ($label === '' || \core_text::strlen($label) > 100) {
            throw new \invalid_parameter_exception(get_string('invalidlabel', 'local_activityicons'));
        }
        $factory = \core\lock\lock_config::get_lock_factory('local_activityicons');
        $lock = $factory->get_lock('poolmetadata', 10);
        if (!$lock) {
            throw new \moodle_exception('locktimeout', 'error');
        }
        try {
            $transaction = $DB->start_delegated_transaction();
            $record = $DB->get_record(self::TABLE, ['iconkey' => $key]);
            if (!$record) {
                $record = (object) ['iconkey' => $key, 'label' => $label, 'timecreated' => time()];
                $record->id = $DB->insert_record(self::TABLE, $record);
            }
            $fs = get_file_storage();
            $contextid = \context_system::instance()->id;
            $name = 'icon.' . $image['extension'];
            if (!$fs->file_exists($contextid, 'local_activityicons', self::FILEAREA, $record->id, '/', $name)) {
                $fs->create_file_from_string([
                    'contextid' => $contextid, 'component' => 'local_activityicons',
                    'filearea' => self::FILEAREA, 'itemid' => $record->id, 'filepath' => '/',
                    'filename' => $name, 'mimetype' => $image['mimetype'], 'userid' => 0,
                ], $image['content']);
            }
            $transaction->allow_commit();
            self::reset_cache();
            return $record;
        } finally {
            $lock->release();
        }
    }

    public static function reset_cache(): void {
        \cache::make_from_params(\cache_store::MODE_REQUEST, 'local_activityicons', 'pool')->purge();
    }

    /** Global use includes direct assignments, mappings even when automation is off, and the fallback. */
    public static function used_keys(): array {
        global $DB;
        $keys = $DB->get_fieldset_select(repository::TABLE, 'iconkey', 'mode = :mode',
            ['mode' => repository::MODE_MANUAL]);
        $keys = array_merge($keys, array_values(h5p_resolver::configured_mapping()));
        $keys[] = get_config('local_activityicons', 'h5pfallback');
        return array_fill_keys(array_filter($keys), true);
    }

    /** Delete only a custom, currently unreferenced asset. UI visibility alone never authorises deletion. */
    public static function delete_unused(int $id): void {
        global $DB;
        require_capability('local/activityicons:managepool', \context_system::instance());
        $DB->get_record(self::TABLE, ['id' => $id], 'id', MUST_EXIST);
        $result = self::delete_unused_batch([$id]);
        if ($result['kept']) {
            throw new \moodle_exception('iconinuse', 'local_activityicons');
        }
    }

    /** Delete every currently unused selected asset and retain referenced selections. */
    public static function delete_unused_batch(array $ids): array {
        global $DB;
        require_capability('local/activityicons:managepool', \context_system::instance());
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), fn($id) => $id > 0)));
        if (!$ids || count($ids) > 500) {
            throw new \invalid_parameter_exception(get_string('invalidiconselection', 'local_activityicons'));
        }
        $lock = \core\lock\lock_config::get_lock_factory('local_activityicons')->get_lock('poolmetadata', 10);
        if (!$lock) {
            throw new \moodle_exception('locktimeout', 'error');
        }
        try {
            self::reset_cache();
            // Drop request-local config acceleration after waiting for another writer's lock.
            \cache::make('core', 'config')->delete('local_activityicons');
            $records = $DB->get_records_list(self::TABLE, 'id', $ids);
            $used = self::used_keys();
            $kept = array_filter($records, fn($record) => isset($used[$record->iconkey]));
            $deletable = array_diff_key($records, $kept);
            $transaction = $DB->start_delegated_transaction();
            foreach ($deletable as $record) {
                get_file_storage()->delete_area_files(\context_system::instance()->id,
                    'local_activityicons', self::FILEAREA, $record->id);
                $DB->delete_records(self::TABLE, ['id' => $record->id]);
            }
            $transaction->allow_commit();
            self::reset_cache();
            return ['deleted' => count($deletable), 'kept' => count($kept)];
        } finally {
            $lock->release();
        }
    }

    public static function file(int $id): ?\stored_file {
        $files = get_file_storage()->get_area_files(\context_system::instance()->id,
            'local_activityicons', self::FILEAREA, $id, 'id', false);
        return $files ? reset($files) : null;
    }

    public static function url(\stdClass $record): ?\moodle_url {
        $file = self::file((int) $record->id);
        if (!$file) {
            return null;
        }
        $url = \moodle_url::make_pluginfile_url($file->get_contextid(), 'local_activityicons', self::FILEAREA,
            $record->id, '/', $file->get_filename());
        $url->param('filtericon', 1);
        return $url;
    }

    /** Only transport custom assets referenced by this course or its active automatic mapping. */
    public static function backup_records(int $courseid, ?int $cmid = null): array {
        $repository = new repository();
        $keys = [];
        $automatic = $repository->course_auto_h5p_enabled($courseid);
        foreach ($repository->records_for_course($courseid) as $assignment) {
            if ($cmid !== null && (int) $assignment->cmid !== $cmid) {
                continue;
            }
            if ($assignment->mode === repository::MODE_MANUAL) {
                $keys[] = $assignment->iconkey;
            } else if ($assignment->mode === repository::MODE_AUTOMATIC) {
                $automatic = true;
            }
        }
        if ($automatic) {
            $keys = array_merge($keys, array_values(h5p_resolver::configured_mapping()));
            $keys[] = get_config('local_activityicons', 'h5pfallback');
        }
        $result = [];
        foreach (self::records() as $key => $record) {
            if (in_array($key, $keys, true) && self::file((int) $record->id)) {
                $record->filescontextid = \context_system::instance()->id;
                $result[] = $record;
            }
        }
        return $result;
    }
}
