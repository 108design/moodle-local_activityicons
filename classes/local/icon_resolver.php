<?php
namespace local_activityicons\local;

use cm_info;
use renderer_base;

/** Resolves stored choices into the icon Moodle should render for a course module. */
final class icon_resolver {
    public function __construct(
        private readonly repository $repository = new repository(),
        private readonly h5p_resolver $h5presolver = new h5p_resolver(),
    ) {
    }

    /** Return the bundled icon key that is effective for this module, or null for Moodle's icon. */
    public function effective_key(cm_info $cm, ?\stdClass $record = null): ?string {
        $record ??= $this->repository->get_record((int) $cm->id);
        if ($record) {
            if ($record->mode === repository::MODE_DEFAULT) {
                return null;
            }
            if ($record->mode === repository::MODE_MANUAL && !empty($record->iconkey)) {
                if (in_array($record->iconkey, icon_catalog::keys(), true)) {
                    return $record->iconkey;
                }
                debugging('Activity Icons ignored an unknown stored icon key.', DEBUG_DEVELOPER);
                return null;
            }
            if ($record->mode === repository::MODE_AUTOMATIC) {
                return $this->h5presolver->resolve($cm);
            }
        }

        if (!$record && h5p_resolver::supports_module($cm->modname)
                && $this->repository->course_auto_h5p_enabled((int) $cm->course)) {
            return $this->h5presolver->resolve($cm);
        }
        return null;
    }

    /** Describe a custom icon for the browser client, or null when Moodle's icon remains effective. */
    public function custom_descriptor(cm_info $cm, renderer_base $output, ?\stdClass $record = null): ?array {
        $key = $this->effective_key($cm, $record);
        if ($key === null) {
            return null;
        }
        return [
            'cmid' => (int) $cm->id,
            'kind' => 'custom',
            'key' => $key,
            'url' => icon_catalog::url($key, $output)->out(false),
            'filtered' => true,
            'branded' => false,
        ];
    }

    /** Describe the unmodified icon for an AJAX response after a custom choice was removed. */
    public function default_descriptor(cm_info $cm): array {
        $url = $cm->get_icon_url();
        return [
            'cmid' => (int) $cm->id,
            'kind' => 'default',
            'key' => null,
            'url' => $url->out(false),
            'filtered' => (bool) $url->get_param('filtericon'),
            'branded' => (bool) component_callback('mod_' . $cm->modname, 'is_branded', [], false),
        ];
    }

    /** Apply custom URLs to Moodle's cm_info objects and return client descriptors keyed by cmid. */
    public function apply_for_course(int $courseid, renderer_base $output, ?array $records = null): array {
        $modinfo = get_fast_modinfo($courseid);
        $records ??= $this->repository->records_for_course($courseid);
        $descriptors = [];
        foreach ($modinfo->get_cms() as $cmid => $cm) {
            $descriptor = $this->custom_descriptor($cm, $output, $records[$cmid] ?? null);
            if ($descriptor === null) {
                continue;
            }
            // Initialise Moodle's dynamic icon data before replacing just the URL.
            $cm->get_icon_url();
            $cm->set_icon_url(new \moodle_url($descriptor['url']));
            $descriptors[(string) $cmid] = $descriptor;
        }
        return $descriptors;
    }
}
