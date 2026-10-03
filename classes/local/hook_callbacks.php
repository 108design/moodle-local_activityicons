<?php
namespace local_activityicons\local;

use core\hook\output\before_http_headers;

/** Moodle output integration for server-rendered and dynamically inserted activity icons. */
final class hook_callbacks {
    public static function before_http_headers(before_http_headers $hook): void {
        global $DB, $PAGE;

        if (during_initial_install() || (defined('CLI_SCRIPT') && CLI_SCRIPT)
                || (int) get_config('local_activityicons', 'version') < 2026091003) {
            return;
        }

        $courseid = (int) ($PAGE->course->id ?? 0);
        $iscourse = $courseid > SITEID;
        $canmanage = false;
        $editing = false;
        $descriptors = [];
        $selections = [];
        $repository = new repository();

        if ($iscourse) {
            $context = \context_course::instance($courseid);
            $canmanage = has_capability('local/activityicons:manage', $context);
            $editing = $canmanage && $PAGE->user_is_editing();
            $records = $repository->records_for_course($courseid);
            if ($records || $repository->course_auto_h5p_enabled($courseid)) {
                $descriptors = (new icon_resolver($repository))->apply_for_course(
                    $courseid,
                    $hook->renderer,
                    $records
                );
            }
            foreach ($records as $cmid => $record) {
                $selections[(string) $cmid] = self::selection_for_record($record);
            }
        }

        $dynamicsetting = get_config('local_activityicons', 'dynamicfallback');
        $dynamicfallback = $dynamicsetting === false ? true : (bool) $dynamicsetting;
        $hasassignments = $DB->record_exists(repository::TABLE, []);
        $hascourseauto = $DB->record_exists(repository::COURSE_TABLE, [
            'autoh5pmode' => repository::COURSE_AUTO_ENABLED,
        ]);
        if (!$editing && !$descriptors && !($dynamicfallback && ($hasassignments
                || $hascourseauto || get_config('local_activityicons', 'autoh5p')))) {
            return;
        }

        $PAGE->requires->css('/local/activityicons/styles.css');
        $PAGE->requires->js_call_amd('local_activityicons/activityicons', 'init', [[
            'courseid' => $iscourse ? $courseid : 0,
            'currentcmid' => (int) ($PAGE->cm->id ?? 0),
            'canmanage' => $canmanage,
            'editing' => $editing,
            'dynamicfallback' => $dynamicfallback,
            'descriptors' => $descriptors,
            'selections' => $selections,
            'h5pcmids' => $canmanage ? array_values(array_map(static fn($cm) => (int) $cm->id,
                array_filter(get_fast_modinfo($courseid)->get_cms(),
                    static fn($cm) => h5p_resolver::supports_module($cm->modname)))) : [],
            'catalog' => $canmanage ? icon_catalog::client_entries($hook->renderer) : [],
            'strings' => [
                'choose' => get_string('chooseicon', 'local_activityicons'),
                'inherit' => get_string('selectioninherit', 'local_activityicons'),
                'inheritDescription' => get_string('selectioninherit_desc', 'local_activityicons'),
                'default' => get_string('selectiondefault', 'local_activityicons'),
                'defaultDescription' => get_string('selectiondefault_desc', 'local_activityicons'),
                'auto' => get_string('selectionauto', 'local_activityicons'),
                'autoDescription' => get_string('selectionauto_desc', 'local_activityicons'),
                'close' => get_string('closepicker', 'local_activityicons'),
                'search' => get_string('searchicons', 'local_activityicons'),
                'saved' => get_string('iconsaved', 'local_activityicons'),
            ],
        ]]);
    }

    private static function selection_for_record(\stdClass $record): string {
        if ($record->mode === repository::MODE_DEFAULT) {
            return repository::SELECTION_DEFAULT;
        }
        if ($record->mode === repository::MODE_AUTOMATIC) {
            return repository::SELECTION_AUTO;
        }
        if ($record->mode === repository::MODE_MANUAL) {
            return repository::ICON_PREFIX . $record->iconkey;
        }
        return repository::SELECTION_INHERIT;
    }
}
