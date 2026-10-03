<?php
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

    return true;
}
