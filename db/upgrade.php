<?php
// This file is part of Moodle - https://moodle.org/
/**
 * This file is part of a 108design source-available software product.
 *
 * Use and modification are permitted only under the Software License included
 * with this distribution. See LICENSE.md for the full terms.
 *
 * File documentation for db/upgrade.php.
 *
 * @package   local_activityicons
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license   See LICENSE.md for the full terms.
 */
defined('MOODLE_INTERNAL') || die();

/** Upgrade Activity Icons. */
function xmldb_local_activityicons_upgrade(int $oldversion): bool {
    global $DB;

    $dbman = $DB->get_manager();
    if ($oldversion < 2026091003) {
        $table = new xmldb_table('local_activityicons_course');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('autoh5pmode', XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('course_fk', XMLDB_KEY_FOREIGN_UNIQUE, ['courseid'], 'course', ['id']);
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        $pool = new xmldb_table('local_activityicons_pool');
        $pool->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $pool->add_field('iconkey', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL);
        $pool->add_field('label', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL);
        $pool->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $pool->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $pool->add_key('iconkey_uq', XMLDB_KEY_UNIQUE, ['iconkey']);
        if (!$dbman->table_exists($pool)) {
            $dbman->create_table($pool);
        }
        upgrade_plugin_savepoint(true, 2026091003, 'local', 'activityicons');
    }

    if ($oldversion < 2026091112) {
        // The replacement starter set uses h5p.svg as its explicit generic H5P icon.
        if (get_config('local_activityicons', 'h5pfallback') === 'interactive') {
            set_config('h5pfallback', 'h5p', 'local_activityicons');
        }
        $DB->set_field('local_activityicons_map', 'iconkey', 'h5p', ['iconkey' => 'interactive']);
        $mapping = get_config('local_activityicons', 'h5pmapping');
        if (is_string($mapping) && $mapping !== '') {
            $mapping = preg_replace(
                '/(^[ \t]*H5P\.[a-z0-9_]+[ \t]*=[ \t]*)interactive([ \t]*$)/mi',
                '$1h5p$2',
                $mapping
            );
            set_config('h5pmapping', $mapping, 'local_activityicons');
        }
        upgrade_plugin_savepoint(true, 2026091112, 'local', 'activityicons');
    }

    if ($oldversion < 2026111701) {
        $groups = new xmldb_table('local_activityicons_groups');
        $groups->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $groups->add_field('name', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL);
        $groups->add_field('sortorder', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $groups->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        if (!$dbman->table_exists($groups)) {
            $dbman->create_table($groups);
        }

        $members = new xmldb_table('local_activityicons_grpicon');
        $members->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $members->add_field('groupid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $members->add_field('iconkey', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL);
        $members->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $members->add_key('group_fk', XMLDB_KEY_FOREIGN, ['groupid'], 'local_activityicons_groups', ['id']);
        $members->add_index('groupicon_uix', XMLDB_INDEX_UNIQUE, ['groupid', 'iconkey']);
        if (!$dbman->table_exists($members)) {
            $dbman->create_table($members);
        }

        $pool = new xmldb_table('local_activityicons_pool');
        $groupid = new xmldb_field('groupid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'label');
        if (!$dbman->field_exists($pool, $groupid)) {
            $dbman->add_field($pool, $groupid);
        }
        $groupindex = new xmldb_index('group_ix', XMLDB_INDEX_NOTUNIQUE, ['groupid']);
        if (!$dbman->index_exists($pool, $groupindex)) {
            $dbman->add_index($pool, $groupindex);
        }

        upgrade_plugin_savepoint(true, 2026111701, 'local', 'activityicons');
    }

    if ($oldversion < 2026111704) {
        $table = new xmldb_table('local_activityicons_groups');
        $field = new xmldb_field('referenceicon', XMLDB_TYPE_CHAR, '64', null, null, null, null, 'sortorder');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2026111704, 'local', 'activityicons');
    }

    return true;
}
