<?php
namespace local_activityicons\local;

use invalid_parameter_exception;

/** Persistence boundary for per-course-module icon choices. */
final class repository {
    public const TABLE = 'local_activityicons_map';
    public const COURSE_TABLE = 'local_activityicons_course';
    public const MODE_DEFAULT = 'default';
    public const MODE_AUTOMATIC = 'automatic';
    public const MODE_MANUAL = 'manual';
    public const SELECTION_INHERIT = 'inherit';
    public const SELECTION_DEFAULT = 'default';
    public const SELECTION_AUTO = 'auto';
    public const ICON_PREFIX = 'icon:';
    public const COURSE_AUTO_INHERIT = 'inherit';
    public const COURSE_AUTO_ENABLED = 'enabled';
    public const COURSE_AUTO_DISABLED = 'disabled';

    /** Return assignment records keyed by course-module id. */
    public function records_for_course(int $courseid): array {
        global $DB;

        $result = [];
        foreach ($DB->get_records(self::TABLE, ['courseid' => $courseid]) as $record) {
            $result[(int) $record->cmid] = $record;
        }
        return $result;
    }

    public function get_record(int $cmid): ?\stdClass {
        global $DB;

        $record = $DB->get_record(self::TABLE, ['cmid' => $cmid]);
        return $record ?: null;
    }

    /** Return the form/client selection token represented by a record. */
    public function get_selection(int $cmid): string {
        $record = $this->get_record($cmid);
        if (!$record) {
            return self::SELECTION_INHERIT;
        }
        if ($record->mode === self::MODE_DEFAULT) {
            return self::SELECTION_DEFAULT;
        }
        if ($record->mode === self::MODE_AUTOMATIC) {
            return self::SELECTION_AUTO;
        }
        if ($record->mode === self::MODE_MANUAL && !empty($record->iconkey)) {
            return self::ICON_PREFIX . $record->iconkey;
        }
        return self::SELECTION_INHERIT;
    }

    /** Store a validated selection. The inherit token removes the explicit record. */
    public function set_selection(int $courseid, int $cmid, string $selection): string {
        $lock = \core\lock\lock_config::get_lock_factory('local_activityicons')->get_lock('poolmetadata', 10);
        if (!$lock) {
            throw new \moodle_exception('locktimeout', 'error');
        }
        try {
            pool::reset_cache();
            return $this->set_selection_locked($courseid, $cmid, $selection);
        } finally {
            $lock->release();
        }
    }

    /** Store a selection while this repository holds the shared poolmetadata lock. */
    private function set_selection_locked(int $courseid, int $cmid, string $selection): string {
        global $DB;

        $this->require_course_module($courseid, $cmid);
        [$mode, $iconkey] = self::normalise_selection($selection);
        if ($mode === self::SELECTION_INHERIT) {
            $DB->delete_records(self::TABLE, ['cmid' => $cmid]);
            \course_modinfo::purge_course_module_cache($courseid, $cmid);
            return self::SELECTION_INHERIT;
        }

        $now = time();
        $record = $DB->get_record(self::TABLE, ['cmid' => $cmid]);
        if ($record) {
            $record->courseid = $courseid;
            $record->mode = $mode;
            $record->iconkey = $iconkey;
            $record->timemodified = $now;
            $DB->update_record(self::TABLE, $record);
        } else {
            $DB->insert_record(self::TABLE, (object) [
                'courseid' => $courseid,
                'cmid' => $cmid,
                'mode' => $mode,
                'iconkey' => $iconkey,
                'timecreated' => $now,
                'timemodified' => $now,
            ]);
        }
        \course_modinfo::purge_course_module_cache($courseid, $cmid);
        return $mode === self::MODE_MANUAL ? self::ICON_PREFIX . $iconkey : $selection;
    }

    /** Validate a selection and return storage mode plus optional icon key. */
    public static function normalise_selection(string $selection): array {
        $selection = trim($selection);
        if ($selection === self::SELECTION_INHERIT) {
            return [self::SELECTION_INHERIT, null];
        }
        if ($selection === self::SELECTION_DEFAULT) {
            return [self::MODE_DEFAULT, null];
        }
        if ($selection === self::SELECTION_AUTO) {
            return [self::MODE_AUTOMATIC, null];
        }
        if (strpos($selection, self::ICON_PREFIX) === 0) {
            $iconkey = substr($selection, strlen(self::ICON_PREFIX));
            $iconkey = icon_catalog::normalise($iconkey);
            return [self::MODE_MANUAL, $iconkey];
        }
        throw new invalid_parameter_exception(get_string('invalidselection', 'local_activityicons'));
    }

    public function delete_course_module(int $cmid): void {
        global $DB;
        $DB->delete_records(self::TABLE, ['cmid' => $cmid]);
    }

    public function delete_course(int $courseid): void {
        global $DB;
        $DB->delete_records(self::TABLE, ['courseid' => $courseid]);
        $DB->delete_records(self::COURSE_TABLE, ['courseid' => $courseid]);
    }

    public function get_course_auto_mode(int $courseid): string {
        global $DB;

        $mode = $DB->get_field(self::COURSE_TABLE, 'autoh5pmode', ['courseid' => $courseid]);
        return $mode === false ? self::COURSE_AUTO_INHERIT : (string) $mode;
    }

    public function course_auto_h5p_enabled(int $courseid): bool {
        $mode = $this->get_course_auto_mode($courseid);
        if ($mode === self::COURSE_AUTO_ENABLED) {
            return true;
        }
        if ($mode === self::COURSE_AUTO_DISABLED) {
            return false;
        }
        return (bool) get_config('local_activityicons', 'autoh5p');
    }

    public function set_course_auto_mode(int $courseid, string $mode): void {
        global $DB;

        if (!in_array($mode, [self::COURSE_AUTO_INHERIT, self::COURSE_AUTO_ENABLED,
                self::COURSE_AUTO_DISABLED], true)) {
            throw new invalid_parameter_exception(get_string('invalidcourseautomode', 'local_activityicons'));
        }
        if (!$DB->record_exists('course', ['id' => $courseid])) {
            throw new invalid_parameter_exception(get_string('invalidcourse', 'error'));
        }
        if ($mode === self::COURSE_AUTO_INHERIT) {
            $DB->delete_records(self::COURSE_TABLE, ['courseid' => $courseid]);
            \course_modinfo::clear_instance_cache($courseid);
            return;
        }

        $now = time();
        $record = $DB->get_record(self::COURSE_TABLE, ['courseid' => $courseid]);
        if ($record) {
            $record->autoh5pmode = $mode;
            $record->timemodified = $now;
            $DB->update_record(self::COURSE_TABLE, $record);
        } else {
            $DB->insert_record(self::COURSE_TABLE, (object) [
                'courseid' => $courseid,
                'autoh5pmode' => $mode,
                'timecreated' => $now,
                'timemodified' => $now,
            ]);
        }
        \course_modinfo::clear_instance_cache($courseid);
    }

    /** Restore original icons for all existing activities and disable inherited H5P automation. */
    public function reset_course(int $courseid): int {
        global $DB;

        $transaction = $DB->start_delegated_transaction();
        $count = $DB->count_records(self::TABLE, ['courseid' => $courseid]);
        $DB->delete_records(self::TABLE, ['courseid' => $courseid]);
        $this->set_course_auto_mode($courseid, self::COURSE_AUTO_DISABLED);
        $now = time();
        foreach ($DB->get_records('course_modules', ['course' => $courseid], '', 'id') as $cm) {
            $DB->insert_record(self::TABLE, (object) [
                'courseid' => $courseid, 'cmid' => $cm->id, 'mode' => self::MODE_DEFAULT,
                'iconkey' => null, 'timecreated' => $now, 'timemodified' => $now,
            ]);
        }
        $transaction->allow_commit();
        \course_modinfo::clear_instance_cache($courseid);
        return $count;
    }

    private function require_course_module(int $courseid, int $cmid): void {
        global $DB;
        if (!$DB->record_exists('course_modules', ['id' => $cmid, 'course' => $courseid])) {
            throw new invalid_parameter_exception(get_string('unknowncoursemodule', 'local_activityicons'));
        }
    }
}
