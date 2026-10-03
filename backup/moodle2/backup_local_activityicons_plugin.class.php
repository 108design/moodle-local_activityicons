<?php

/** Backup integration for per-activity icon assignments. */
class backup_local_activityicons_plugin extends backup_local_plugin {
    /** Module connection also runs for activity duplication and imports without course-setting restore. */
    protected function define_module_plugin_structure(): backup_nested_element {
        $plugin = $this->get_plugin_element(null);
        $wrapper = new backup_nested_element($this->get_recommended_name());
        $plugin->add_child($wrapper);
        $pool = new backup_nested_element('poolicons');
        $wrapper->add_child($pool);
        $icon = new backup_nested_element('poolicon', ['id'], ['iconkey', 'label', 'filescontextid']);
        $pool->add_child($icon);
        $icon->set_source_array(\local_activityicons\local\pool::backup_records(
            (int) $this->task->get_courseid(), (int) $this->task->get_moduleid()));
        $icon->annotate_files('local_activityicons', 'pool', 'id', context_system::instance()->id);
        $assignments = new backup_nested_element('assignments');
        $wrapper->add_child($assignments);
        $assignment = new backup_nested_element('assignment', ['id'], ['cmid', 'mode', 'iconkey']);
        $assignments->add_child($assignment);
        $assignment->set_source_table('local_activityicons_map', ['cmid' => backup::VAR_MODID]);
        return $plugin;
    }

    protected function define_course_plugin_structure(): backup_nested_element {
        $plugin = $this->get_plugin_element(null);
        $wrapper = new backup_nested_element($this->get_recommended_name());
        $plugin->add_child($wrapper);
        $pool = new backup_nested_element('poolicons');
        $wrapper->add_child($pool);
        $icon = new backup_nested_element('poolicon', ['id'], ['iconkey', 'label', 'filescontextid']);
        $pool->add_child($icon);
        $icon->set_source_array(\local_activityicons\local\pool::backup_records((int) $this->task->get_courseid()));
        $icon->annotate_files('local_activityicons', 'pool', 'id', context_system::instance()->id);
        $policy = new backup_nested_element('coursepolicy', ['id'], ['autoh5pmode']);
        $wrapper->add_child($policy);
        $policy->set_source_table('local_activityicons_course', ['courseid' => backup::VAR_COURSEID]);
        $assignments = new backup_nested_element('assignments');
        $wrapper->add_child($assignments);
        $assignment = new backup_nested_element('assignment', ['id'], ['cmid', 'mode', 'iconkey']);
        $assignments->add_child($assignment);
        $assignment->set_source_table('local_activityicons_map', [
            'courseid' => backup::VAR_COURSEID,
        ]);
        return $plugin;
    }
}
