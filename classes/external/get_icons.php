<?php
namespace local_activityicons\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use local_activityicons\local\icon_resolver;
use local_activityicons\local\repository;

/** Resolve icons for activity links inserted outside the current course page. */
final class get_icons extends external_api {
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmids' => new \core_external\external_multiple_structure(
                new external_value(PARAM_INT, 'Course-module id')
            ),
        ]);
    }

    public static function execute(array $cmids): array {
        global $DB, $PAGE;
        $params = self::validate_parameters(self::execute_parameters(), ['cmids' => $cmids]);
        // External functions initialise login state and $PAGE through validate_context(). This uses
        // Moodle's non-redirecting login check for AJAX requests and gives the renderer a valid context.
        self::validate_context(\context_system::instance());
        $cmids = array_slice(array_values(array_unique(array_filter(array_map('intval', $params['cmids'])))), 0, 100);
        if (!$cmids) {
            return ['descriptorsjson' => '{}'];
        }

        $rows = $DB->get_records_list('course_modules', 'id', $cmids, 'course, id', 'id, course');
        $bycourse = [];
        foreach ($rows as $row) {
            $bycourse[(int) $row->course][] = (int) $row->id;
        }

        $result = [];
        $repository = new repository();
        $resolver = new icon_resolver($repository);
        $output = $PAGE->get_renderer('core');
        foreach ($bycourse as $courseid => $coursecmids) {
            $course = get_course($courseid);
            if (!can_access_course($course)) {
                continue;
            }
            self::validate_context(\context_course::instance($courseid));
            $modinfo = get_fast_modinfo($course);
            $records = $repository->records_for_course($courseid);
            foreach ($coursecmids as $cmid) {
                try {
                    $cm = $modinfo->get_cm($cmid);
                    if (!$cm->uservisible) {
                        continue;
                    }
                    $descriptor = $resolver->custom_descriptor($cm, $output, $records[$cmid] ?? null);
                    if ($descriptor !== null) {
                        $result[(string) $cmid] = $descriptor;
                    }
                } catch (\moodle_exception $exception) {
                    debugging($exception->getMessage(), DEBUG_DEVELOPER);
                }
            }
        }
        return ['descriptorsjson' => json_encode($result, JSON_THROW_ON_ERROR)];
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'descriptorsjson' => new external_value(PARAM_RAW, 'JSON-encoded descriptors keyed by course-module id'),
        ]);
    }
}
