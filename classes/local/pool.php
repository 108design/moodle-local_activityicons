<?php
// This file is part of Moodle - https://moodle.org/
/**
 * This file is part of a 108design source-available software product.
 *
 * Use and modification are permitted only under the Software License included
 * with this distribution. See LICENSE.md for the full terms.
 *
 * File documentation for classes/local/pool.php.
 *
 * @package   local_activityicons
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license   See LICENSE.md for the full terms.
 */
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
        return self::store_batch([[$label, $filename, $bytes]])[0];
    }

    /**
     * Validate and import a complete batch under the pool lock. A repeated upload keeps its current group.
     *
     * @param iterable $files Label, original name and bytes for each upload.
     * @param int $groupid Destination, or 0 for Custom Icons.
     * @return \stdClass[] Stored records.
     */
    public static function store_batch(iterable $files, int $groupid = 0): array {
        global $DB;
        $lock = self::metadata_lock();
        try {
            pool_groups::require_destination($groupid);
            $transaction = $DB->start_delegated_transaction();
            try {
                $records = [];
                foreach ($files as [$label, $filename, $bytes]) {
                    $image = image_validator::sanitise($filename, $bytes);
                    $label = trim(clean_param($label, PARAM_TEXT));
                    if ($label === '' || \core_text::strlen($label) > 100) {
                        throw new \invalid_parameter_exception(get_string('invalidlabel', 'local_activityicons'));
                    }
                    $records[] = self::store_validated($label, $image, $groupid);
                }
                $transaction->allow_commit();
            } catch (\Throwable $exception) {
                $transaction->rollback($exception);
            }
            self::reset_cache();
            return $records;
        } finally {
            $lock->release();
        }
    }

    /** Share the existing writer lock with the group editor. */
    public static function metadata_lock(): \core\lock\lock {
        $lock = \core\lock\lock_config::get_lock_factory('local_activityicons')->get_lock('poolmetadata', 10);
        if (!$lock) {
            throw new \moodle_exception('locktimeout', 'error');
        }
        return $lock;
    }

    /** Add the file only when the content key is new or its expected file is missing. */
    private static function store_validated(string $label, array $image, int $groupid): \stdClass {
        global $DB;
        $key = 'custom_' . substr(hash('sha256', $image['content']), 0, 40);
        $record = $DB->get_record(self::TABLE, ['iconkey' => $key]);
        if (!$record) {
            $record = (object) ['iconkey' => $key, 'label' => $label, 'timecreated' => time()];
            if ($groupid) {
                $record->groupid = $groupid;
            }
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
        return $record;
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

    /** The advanced action is available only to site configuration administrators. */
    public static function developer_mode_enabled(): bool {
        $context = \context_system::instance();
        return has_capability('moodle/site:config', $context)
            && has_capability('local/activityicons:managepool', $context)
            && (bool) get_config('local_activityicons', 'developermode');
    }

    /** Return exact direct assignments, H5P types and fallback usage for each selected icon. */
    public static function deletion_usage(array $records): array {
        global $DB;
        if (!$records) {
            return [];
        }
        $ids = array_column($records, 'id', 'iconkey');
        $fallback = get_config('local_activityicons', 'h5pfallback');
        $usage = [];
        foreach ($ids as $key => $id) {
            $usage[$id] = ['assignments' => [], 'types' => [], 'fallback' => $key === $fallback];
        }
        [$insql, $params] = $DB->get_in_or_equal(array_keys($ids), SQL_PARAMS_NAMED, 'icon');
        $params['mode'] = repository::MODE_MANUAL;
        $references = $DB->get_records_select(repository::TABLE, "mode = :mode AND iconkey $insql", $params,
            'id', 'id,courseid,iconkey');
        foreach ($references as $reference) {
            $usage[$ids[$reference->iconkey]]['assignments'][(int) $reference->id] = (int) $reference->courseid;
        }
        foreach (h5p_resolver::configured_mapping() as $machine => $key) {
            if (isset($ids[$key])) {
                $usage[$ids[$key]]['types'][] = 'H5P.' . $machine;
            }
        }
        foreach ($usage as &$details) {
            sort($details['types']);
        }
        unset($details);
        ksort($usage);
        return $usage;
    }

    /** Bind a confirmation to the selected icon ids and the usage that the admin reviewed. */
    public static function usage_hash(array $usage): string {
        return hash('sha256', json_encode($usage, JSON_THROW_ON_ERROR));
    }

    /** Protect used icons unless an administrator explicitly confirmed their current usage. */
    public static function delete_unused_batch(array $ids, bool $force = false, ?string $confirmedusage = null): array {
        global $DB;
        require_capability('local/activityicons:managepool', \context_system::instance());
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), fn($id) => $id > 0)));
        if (!$ids || count($ids) > 500) {
            throw new \invalid_parameter_exception(get_string('invalidiconselection', 'local_activityicons'));
        }
        $lock = self::metadata_lock();
        try {
            self::reset_cache();
            \cache::make('core', 'config')->delete('local_activityicons');
            $records = $DB->get_records_list(self::TABLE, 'id', $ids);
            $usage = self::deletion_usage($records);
            if ($force) {
                require_capability('moodle/site:config', \context_system::instance());
                if (!self::developer_mode_enabled()) {
                    throw new \moodle_exception('developermodedisabled', 'local_activityicons');
                }
                if ($confirmedusage === null || !hash_equals(self::usage_hash($usage), $confirmedusage)) {
                    throw new \moodle_exception('deleteusagechanged', 'local_activityicons');
                }
            }
            $kept = $force ? [] : array_filter($records, static function($record) use ($usage): bool {
                $details = $usage[$record->id];
                return $details['assignments'] || $details['types'] || $details['fallback'];
            });
            $deletable = array_diff_key($records, $kept);
            $transaction = $DB->start_delegated_transaction();
            $courses = [];
            try {
                if ($force && $deletable) {
                    $courses = self::reset_deleted_references($deletable, $usage);
                }
                foreach ($deletable as $record) {
                    get_file_storage()->delete_area_files(\context_system::instance()->id,
                        'local_activityicons', self::FILEAREA, $record->id);
                    pool_groups::remove_upload_links($record->iconkey);
                    $DB->delete_records(self::TABLE, ['id' => $record->id]);
                }
                $transaction->allow_commit();
            } catch (\Throwable $exception) {
                $transaction->rollback($exception);
            }
            self::reset_cache();
            foreach ($courses as $courseid) {
                \course_modinfo::purge_course_cache($courseid);
            }
            return ['deleted' => count($deletable), 'kept' => count($kept)];
        } finally {
            $lock->release();
        }
    }

    /** Replace direct activity choices and affected H5P rules before removing a file. */
    private static function reset_deleted_references(array $records, array $usage): array {
        global $DB;
        $courses = [];
        foreach ($records as $record) {
            foreach ($usage[$record->id]['assignments'] as $assignmentid => $courseid) {
                $DB->update_record(repository::TABLE, (object) [
                    'id' => $assignmentid,
                    'mode' => repository::MODE_DEFAULT,
                    'iconkey' => null,
                    'timemodified' => time(),
                ]);
                $courses[$courseid] = $courseid;
            }
        }
        h5p_resolver::reset_deleted_icons(array_column($records, 'iconkey'));
        return $courses;
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
