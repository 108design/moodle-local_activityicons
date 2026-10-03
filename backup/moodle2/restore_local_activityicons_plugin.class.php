<?php

/** Restore integration for per-activity icon assignments. */
class restore_local_activityicons_plugin extends restore_local_plugin {
    /** @var stdClass[] Assignments waiting until course-module mappings exist. */
    private array $assignments = [];
    private array $poolicons = [];

    protected function define_module_plugin_structure(): array {
        return $this->define_course_plugin_structure();
    }

    public function after_restore_module(): void {
        $this->after_restore_course();
    }

    protected function define_course_plugin_structure(): array {
        return [
            new restore_path_element('local_activityicons_poolicon', $this->get_pathfor('/poolicons/poolicon')),
            new restore_path_element('local_activityicons_coursepolicy', $this->get_pathfor('/coursepolicy')),
            new restore_path_element('local_activityicons_assignment',
                $this->get_pathfor('/assignments/assignment')),
        ];
    }

    public function process_local_activityicons_assignment($data): void {
        $this->assignments[] = (object) $data;
    }

    public function process_local_activityicons_poolicon($data): void {
        $source = (object) $data;
        if ((int) $source->id > 0 && (int) $source->filescontextid > 0) {
            $this->poolicons[] = $source;
            $this->set_mapping('local_activityicons_poolicon', $source->id, $source->id, true,
                $source->filescontextid);
        }
    }

    public function process_local_activityicons_coursepolicy($data): void {
        global $DB;
        $courseid = (int) $this->task->get_courseid();
        $mode = $data['autoh5pmode'] ?? '';
        // Keep an existing destination policy when merging into another course.
        if (!$DB->record_exists('local_activityicons_course', ['courseid' => $courseid])
                && in_array($mode, ['enabled', 'disabled'], true)) {
            (new \local_activityicons\local\repository())->set_course_auto_mode($courseid, $mode);
        }
    }

    public function after_restore_course(): void {
        global $DB;
        $courseid = (int) $this->task->get_courseid();
        $keymap = [];
        $contextid = context_course::instance($courseid)->id;
        foreach ($this->poolicons as $source) {
            $existing = \local_activityicons\local\pool::records()[$source->iconkey] ?? null;
            if ($existing && \local_activityicons\local\pool::file((int) $existing->id)) {
                $keymap[$source->iconkey] = $existing->iconkey;
                continue;
            }
            // A course restore must not grant teachers permission to publish global assets.
            if (!has_capability('local/activityicons:managepool', context_system::instance())) {
                $this->task->log('Skipped a new shared activity icon: pool management permission required.',
                    backup::LOG_WARNING);
                continue;
            }
            // Restore into a non-served course area first. Revalidate before publishing in the system pool.
            restore_dbops::send_files_to_pool($this->task->get_basepath(), $this->task->get_restoreid(),
                'local_activityicons', 'pool', $source->filescontextid, 0,
                'local_activityicons_poolicon', $source->id, $contextid);
            $fs = get_file_storage();
            try {
                $files = $fs->get_area_files($contextid, 'local_activityicons', 'pool', $source->id, 'id', false);
                if (count($files) === 1) {
                    $file = reset($files);
                    if ($file->get_filesize() <= \local_activityicons\local\image_validator::MAX_BYTES) {
                        $record = \local_activityicons\local\pool::store($source->label,
                            $file->get_filename(), $file->get_content());
                        $keymap[$source->iconkey] = $record->iconkey;
                    }
                }
            } catch (invalid_parameter_exception $exception) {
                $this->task->log('Skipped an invalid custom activity icon in the backup.', backup::LOG_WARNING);
            } finally {
                $fs->delete_area_files($contextid, 'local_activityicons', 'pool', $source->id);
            }
        }
        foreach ($this->assignments as $source) {
            $cmid = (int) $this->get_mappingid('course_module', (int) $source->cmid);
            if (!$cmid || $DB->record_exists('local_activityicons_map', ['cmid' => $cmid])) {
                continue;
            }
            $mode = clean_param($source->mode, PARAM_ALPHA);
            $iconkey = empty($source->iconkey) ? null : clean_param($source->iconkey, PARAM_ALPHANUMEXT);
            $iconkey = $keymap[$iconkey] ?? $iconkey;
            if (!in_array($mode, ['default', 'automatic', 'manual'], true)
                    || ($mode === 'manual' && !in_array($iconkey,
                        \local_activityicons\local\icon_catalog::keys(), true))) {
                continue;
            }
            $selection = $mode === 'manual' ? 'icon:' . $iconkey : ($mode === 'automatic' ? 'auto' : 'default');
            try {
                (new \local_activityicons\local\repository())->set_selection($courseid, $cmid, $selection);
            } catch (invalid_parameter_exception $exception) {
                $this->task->log('Skipped an activity icon removed during restore.', backup::LOG_WARNING);
            }
        }
    }
}
