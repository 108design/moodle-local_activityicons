<?php
namespace local_activityicons\external;

use context_course;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use local_activityicons\local\icon_resolver;
use local_activityicons\local\repository;

/** AJAX endpoint for assigning an icon to one course module. */
final class set_icon extends external_api {
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course id'),
            'cmid' => new external_value(PARAM_INT, 'Course-module id'),
            'selection' => new external_value(PARAM_RAW_TRIMMED, 'Validated selection token'),
        ]);
    }

    public static function execute(int $courseid, int $cmid, string $selection): array {
        global $PAGE;
        $params = self::validate_parameters(self::execute_parameters(), compact('courseid', 'cmid', 'selection'));
        $context = context_course::instance((int) $params['courseid']);
        self::validate_context($context);
        require_capability('local/activityicons:manage', $context);
        // AJAX service.php does not initialise $PAGE->context, but the renderer used for a theme-aware
        // icon URL requires it.
        $PAGE->set_context($context);

        $repository = new repository();
        $repository->set_selection((int) $params['courseid'], (int) $params['cmid'], (string) $params['selection']);
        $cm = get_fast_modinfo((int) $params['courseid'])->get_cm((int) $params['cmid']);
        $resolver = new icon_resolver($repository);
        $descriptor = $resolver->custom_descriptor($cm, $PAGE->get_renderer('core'))
            ?? $resolver->default_descriptor($cm);
        $descriptor['selection'] = $repository->get_selection((int) $params['cmid']);
        return ['descriptorjson' => json_encode($descriptor, JSON_THROW_ON_ERROR)];
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'descriptorjson' => new external_value(PARAM_RAW, 'JSON-encoded icon descriptor'),
        ]);
    }
}
