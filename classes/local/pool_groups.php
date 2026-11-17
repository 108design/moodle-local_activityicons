<?php
// This file is part of Moodle - https://moodle.org/
/**
 * This file is part of a 108design source-available software product.
 *
 * Use and modification are permitted only under the Software License included
 * with this distribution. See LICENSE.md for the full terms.
 *
 * File documentation for classes/local/pool_groups.php.
 *
 * @package   local_activityicons
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license   See LICENSE.md for the full terms.
 */
namespace local_activityicons\local;

/** Site-wide organisation of the shared icon catalog. Icon keys remain independent of group membership. */
final class pool_groups {
    private const TABLE = 'local_activityicons_groups';
    private const MEMBERS = 'local_activityicons_grpicon';
    private const SCHEMA_VERSION = 2026111701;

    /** Return configured groups in picker order. */
    public static function records(): array {
        global $DB;
        if ((int) get_config('local_activityicons', 'version') < self::SCHEMA_VERSION) {
            return [];
        }
        return $DB->get_records(self::TABLE, null, 'sortorder, id');
    }

    /** Return group ids by bundled icon key. */
    public static function memberships(): array {
        global $DB;
        if ((int) get_config('local_activityicons', 'version') < self::SCHEMA_VERSION) {
            return [];
        }
        $memberships = [];
        foreach ($DB->get_records(self::MEMBERS) as $member) {
            $memberships[$member->iconkey][] = self::picker_key((int) $member->groupid);
        }
        return $memberships;
    }

    /** Use a separate prefix for site-owned groups so their ids cannot collide with a built-in pool. */
    public static function picker_key(int $groupid): string {
        return $groupid ? 'custom-' . $groupid : 'custom';
    }

    /** Group destinations for uploads and moves. */
    public static function options(): array {
        $options = [0 => get_string('poolcustom', 'local_activityicons')];
        foreach (self::records() as $group) {
            $options[$group->id] = $group->name;
        }
        return $options;
    }

    /** Validate an upload destination while the caller holds the common pool lock. */
    public static function require_destination(int $id): void {
        global $DB;
        if ($id !== 0 && ($id < 0 || !$DB->record_exists(self::TABLE, ['id' => $id]))) {
            throw new \invalid_parameter_exception(get_string('groupmissing', 'local_activityicons'));
        }
    }

    /** Read a group's saved bundled choices. */
    public static function bundled_keys(int $id): array {
        global $DB;
        return $DB->get_fieldset_select(self::MEMBERS, 'iconkey', 'groupid = ?', [$id]);
    }

    /** Check the name for the form and again under lock before saving. */
    public static function name_error(string $name, int $id): ?string {
        if ($name === '' || \core_text::strlen($name) > 100) {
            return get_string('invalidgroupname', 'local_activityicons');
        }
        $normalised = \core_text::strtolower($name);
        foreach (self::options() as $groupid => $existing) {
            if (($groupid === 0 || $groupid !== $id) && \core_text::strtolower($existing) === $normalised) {
                return get_string('duplicategroupname', 'local_activityicons');
            }
        }
        return null;
    }

    /** Save the display name and set of references as one change. */
    public static function save(int $id, string $name, ?array $keys = null, ?string $reference = null): int {
        return self::write(function() use ($id, $name, $keys, $reference): int {
            global $DB;
            $name = trim(clean_param($name, PARAM_TEXT));
            $error = self::name_error($name, $id);
            if ($error !== null) {
                throw new \invalid_parameter_exception($error);
            }
            if ($keys !== null && array_diff($keys, icon_catalog::bundled_keys())) {
                throw new \invalid_parameter_exception(get_string('invalidselection', 'local_activityicons'));
            }
            if ($id) {
                self::require_destination($id);
                $DB->set_field(self::TABLE, 'name', $name, ['id' => $id]);
            } else {
                $next = 1 + (int) $DB->get_field_sql('SELECT MAX(sortorder) FROM {' . self::TABLE . '}');
                $id = (int) $DB->insert_record(self::TABLE, ['name' => $name, 'sortorder' => $next]);
            }
            if ($keys !== null) {
                self::replace_keys($id, array_values(array_unique(array_merge($keys, array_keys(self::copied_uploads($id))))));
            }
            if ($reference !== null) {
                if ($reference !== 'auto' && !self::contains_icon($id, $reference)) {
                    throw new \invalid_parameter_exception(get_string('poolreferenceinvalid', 'local_activityicons'));
                }
                $DB->set_field(self::TABLE, 'referenceicon', $reference === 'auto' ? null : $reference, ['id' => $id]);
            }
            return $id;
        });
    }

    /** Check current membership when saving a reference, under the pool lock. */
    private static function contains_icon(int $id, string $key): bool {
        $upload = pool::records()[$key] ?? null;
        if ($upload && (int) $upload->groupid === $id) {
            return true;
        }
        return in_array($key, icon_catalog::keys(), true) && in_array($key, self::bundled_keys($id), true);
    }

    /** Copied uploads already offered in this pool. */
    public static function copied_uploads(int $id): array {
        return array_intersect_key(pool::records(), array_fill_keys(self::bundled_keys($id), true));
    }

    private static function replace_keys(int $id, array $keys): void {
        global $DB;
        $existing = self::bundled_keys($id);
        foreach (array_diff($existing, $keys) as $key) {
            $DB->delete_records(self::MEMBERS, ['groupid' => $id, 'iconkey' => $key]);
        }
        foreach (array_diff($keys, $existing) as $key) {
            $DB->insert_record(self::MEMBERS, ['groupid' => $id, 'iconkey' => $key]);
        }
    }

    /** Copy catalogue assets and uploads by key, without duplicating files. */
    public static function copy_icons(array $keys, int $destination): void {
        self::write(function() use ($keys, $destination): void {
            global $DB;
            if ($destination <= 0) {
                throw new \invalid_parameter_exception(get_string('groupmissing', 'local_activityicons'));
            }
            self::require_destination($destination);
            $existing = array_fill_keys(self::bundled_keys($destination), true);
            $uploads = pool::records();
            foreach (array_unique($keys) as $key) {
                icon_catalog::normalise($key);
                if (isset($existing[$key]) || (int) ($uploads[$key]->groupid ?? 0) === $destination) {
                    continue;
                }
                $DB->insert_record(self::MEMBERS, ['groupid' => $destination, 'iconkey' => $key]);
            }
        });
    }

    /** Remove additional appearances of icons from one custom pool. */
    public static function remove_copies(array $keys, int $source): void {
        self::write(function() use ($keys, $source): void {
            global $DB;
            if ($source <= 0) {
                throw new \invalid_parameter_exception(get_string('groupmissing', 'local_activityicons'));
            }
            self::require_destination($source);
            [$where, $params] = $DB->get_in_or_equal($keys, SQL_PARAMS_NAMED);
            $DB->delete_records_select(self::MEMBERS, "groupid = :source AND iconkey $where", ['source' => $source] + $params);
        });
    }

    /** Validate the same bounded set of upload IDs at preview and save. */
    public static function selected_uploads(array $ids): array {
        global $DB;
        $ids = array_values(array_unique(array_map('intval', $ids)));
        if (!$ids || count($ids) > 500) {
            throw new \invalid_parameter_exception(get_string('selecticonsfirst', 'local_activityicons'));
        }
        $records = $DB->get_records_list(pool::TABLE, 'id', $ids, 'label, id');
        if (count($records) !== count($ids)) {
            throw new \invalid_parameter_exception(get_string('invalidiconselection', 'local_activityicons'));
        }
        return $records;
    }

    /** Clear copies when the underlying upload is deleted. Caller holds the pool lock. */
    public static function remove_upload_links(string $key): void {
        global $DB;
        $DB->delete_records(self::MEMBERS, ['iconkey' => $key]);
    }

    /** Move selected uploads together. Neither the stored file nor its content-derived key changes. */
    public static function move_uploads(array $ids, int $destination, ?int $source = null): void {
        self::write(function() use ($ids, $destination, $source): void {
            global $DB;
            self::require_destination($destination);
            $records = self::selected_uploads($ids);
            foreach ($records as $record) {
                if ($source !== null && (int) $record->groupid !== $source) {
                    throw new \invalid_parameter_exception(get_string('invalidiconselection', 'local_activityicons'));
                }
            }
            [$where, $params] = $DB->get_in_or_equal(array_keys($records));
            $DB->set_field_select(pool::TABLE, 'groupid', $destination ?: null, "id $where", $params);
            if ($destination) {
                [$where, $params] = $DB->get_in_or_equal(array_column($records, 'iconkey'), SQL_PARAMS_NAMED);
                $DB->delete_records_select(self::MEMBERS, "groupid = :destination AND iconkey $where",
                    ['destination' => $destination] + $params);
            }
        });
    }

    /** Move an existing group one place in the list. */
    public static function reorder(int $id, int $direction): void {
        self::write(function() use ($id, $direction): void {
            global $DB;
            $groups = array_values(self::records());
            $position = array_search($id, array_map(static fn($group) => (int) $group->id, $groups), true);
            if ($position === false || !in_array($direction, [-1, 1], true)) {
                throw new \invalid_parameter_exception(get_string('groupmissing', 'local_activityicons'));
            }
            $neighbour = $groups[$position + $direction] ?? null;
            if ($neighbour) {
                $group = $groups[$position];
                $DB->set_field(self::TABLE, 'sortorder', $neighbour->sortorder, ['id' => $group->id]);
                $DB->set_field(self::TABLE, 'sortorder', $group->sortorder, ['id' => $neighbour->id]);
            }
        });
    }

    /** Removing the group returns uploads to the default pool and removes its bundled references. */
    public static function delete(int $id): void {
        self::write(function() use ($id): void {
            global $DB;
            if ($id <= 0) {
                throw new \invalid_parameter_exception(get_string('groupmissing', 'local_activityicons'));
            }
            self::require_destination($id);
            $DB->set_field(pool::TABLE, 'groupid', null, ['groupid' => $id]);
            $DB->delete_records(self::MEMBERS, ['groupid' => $id]);
            $DB->delete_records(self::TABLE, ['id' => $id]);
        });
    }

    /** Serialize metadata writes with uploads, label edits, icon removal and assignment. */
    private static function write(callable $callback): mixed {
        global $DB;
        require_capability('local/activityicons:managepool', \context_system::instance());
        $lock = pool::metadata_lock();
        try {
            pool::reset_cache();
            $transaction = $DB->start_delegated_transaction();
            try {
                $result = $callback();
                $transaction->allow_commit();
            } catch (\Throwable $exception) {
                $transaction->rollback($exception);
            }
            pool::reset_cache();
            return $result;
        } finally {
            $lock->release();
        }
    }
}
