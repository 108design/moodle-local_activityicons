<?php
namespace local_activityicons\local;

use cm_info;
use context_module;

/** Resolve the top-level H5P library to a stable semantic icon key. */
final class h5p_resolver {
    private const MANIFEST_MAX_BYTES = 65536;

    /** Conservative defaults. Administrators can replace or extend these in the plugin settings. */
    private const DEFAULT_MAPPING = [
        'interactivevideo' => 'video',
        'video' => 'video',
        'coursepresentation' => 'presentation',
        'branchingscenario' => 'branching',
        'audiorecorder' => 'audio',
        'audio' => 'audio',
        'dictation' => 'audio',
        'imagehotspots' => 'image',
        'imageslider' => 'image',
        'imagesequencing' => 'image',
        'agamotto' => 'image',
        'memorygame' => 'game',
        'arithmeticquiz' => 'game',
        'multichoice' => 'quiz',
        'truefalse' => 'quiz',
        'blanks' => 'quiz',
        'dragquestion' => 'quiz',
        'dragtext' => 'quiz',
        'markthewords' => 'quiz',
        'summary' => 'quiz',
        'essay' => 'quiz',
        'flashcards' => 'quiz',
        'questionset' => 'quiz',
    ];

    private array $requestcache = [];

    /** Whether this activity module stores H5P content supported by the resolver. */
    public static function supports_module(string $modname): bool {
        return in_array($modname, ['h5pactivity', 'hvp'], true);
    }

    public function resolve(cm_info $cm): ?string {
        if (!self::supports_module($cm->modname)) {
            return null;
        }
        if (array_key_exists($cm->id, $this->requestcache)) {
            return $this->requestcache[$cm->id];
        }

        $result = self::fallback_icon();
        try {
            $machinename = $cm->modname === 'hvp'
                ? self::hvp_machine_name((int) $cm->instance)
                : self::core_machine_name($cm);
            if ($machinename !== null) {
                $result = self::icon_for_machine_name($machinename);
            }
        } catch (\Throwable $exception) {
            debugging('Activity Icons could not resolve H5P type: ' . $exception->getMessage(), DEBUG_DEVELOPER);
        }
        return $this->requestcache[$cm->id] = $result;
    }

    /** Resolve Moodle core's mod_h5pactivity package through the public core H5P API. */
    private static function core_machine_name(cm_info $cm): ?string {
        $context = context_module::instance((int) $cm->id, IGNORE_MISSING);
        if (!$context) {
            return null;
        }
        $files = get_file_storage()->get_area_files(
            $context->id,
            'mod_h5pactivity',
            'package',
            0,
            'id',
            false
        );
        $packagefile = $files ? reset($files) : null;
        if (!$packagefile) {
            return null;
        }

        $file = $packagefile;
        $content = null;
        $url = \moodle_url::make_pluginfile_url(
            $packagefile->get_contextid(),
            $packagefile->get_component(),
            $packagefile->get_filearea(),
            $packagefile->get_itemid(),
            $packagefile->get_filepath(),
            $packagefile->get_filename()
        );
        [$originalfile, $originalcontent] = \core_h5p\api::get_original_content_from_pluginfile_url(
            $url->out(false),
            true,
            true
        );
        if ($originalfile) {
            $file = $originalfile;
            $content = $originalcontent ?: null;
        }
        if (!$content) {
            $content = \core_h5p\api::get_content_from_pathnamehash($file->get_pathnamehash());
        }
        if ($content && !empty($content->mainlibraryid)) {
            $library = \core_h5p\api::get_library((int) $content->mainlibraryid);
            if ($library && !empty($library->machinename)) {
                return (string) $library->machinename;
            }
        }
        return self::package_machine_name($file);
    }

    /** Read only the H5P package manifest when Moodle has not deployed the package yet. */
    private static function package_machine_name(\stored_file $file): ?string {
        $cache = \cache::make('local_activityicons', 'h5ptypes');
        $cachekey = $file->get_contenthash();
        $cached = $cache->get($cachekey);
        if ($cached !== false) {
            return $cached === '' ? null : (string) $cached;
        }

        $machinename = null;
        try {
            $packer = get_file_packer('application/zip');
            $archivepath = $file->copy_content_to_temp('local_activityicons', 'h5p_');
            if (!$archivepath) {
                $cache->set($cachekey, '');
                return null;
            }
            $entries = $packer->list_files($archivepath);
            $manifestvalid = false;
            foreach (is_array($entries) ? $entries : [] as $entry) {
                if ($entry->pathname === 'h5p.json' && !$entry->is_directory
                        && $entry->size <= self::MANIFEST_MAX_BYTES) {
                    $manifestvalid = true;
                    break;
                }
            }
            if ($manifestvalid) {
                $directory = make_request_directory() . '/local_activityicons_' . sha1($cachekey);
                if (check_dir_exists($directory, true, true)
                        && $packer->extract_to_pathname($archivepath, $directory, ['h5p.json'])) {
                    $manifestpath = $directory . '/h5p.json';
                    $json = is_file($manifestpath)
                        ? file_get_contents($manifestpath, false, null, 0, self::MANIFEST_MAX_BYTES + 1)
                        : false;
                    if ($json !== false && strlen($json) <= self::MANIFEST_MAX_BYTES) {
                        $manifest = json_decode($json, true, 16, JSON_THROW_ON_ERROR);
                        $candidate = trim((string) ($manifest['mainLibrary'] ?? ''));
                        if (preg_match('/^[a-z_$][a-z0-9_.$]{1,254}$/i', $candidate)) {
                            $machinename = $candidate;
                        }
                    }
                }
            }
        } catch (\Throwable $exception) {
            debugging('Activity Icons could not read H5P package metadata: ' . $exception->getMessage(), DEBUG_DEVELOPER);
        }
        $cache->set($cachekey, $machinename ?? '');
        return $machinename;
    }

    /** Resolve the official H5P plugin's mod_hvp record without loading its optional PHP classes. */
    private static function hvp_machine_name(int $instanceid): ?string {
        global $DB;

        static $tablesavailable = null;
        if ($tablesavailable === null) {
            $dbman = $DB->get_manager();
            $tablesavailable = $dbman->table_exists(new \xmldb_table('hvp'))
                && $dbman->table_exists(new \xmldb_table('hvp_libraries'));
        }
        if (!$tablesavailable) {
            return null;
        }
        $sql = "SELECT l.machine_name
                  FROM {hvp} h
                  JOIN {hvp_libraries} l ON l.id = h.main_library_id
                 WHERE h.id = :instanceid";
        $machinename = $DB->get_field_sql($sql, ['instanceid' => $instanceid], IGNORE_MISSING);
        return $machinename === false || $machinename === null || $machinename === ''
            ? null
            : (string) $machinename;
    }

    /** Map one exact H5P machine name through the administrator-editable mapping. */
    public static function icon_for_machine_name(
        string $machinename,
        ?array $mapping = null,
        ?string $fallback = null
    ): ?string {
        $mapping ??= self::configured_mapping();
        $fallback ??= self::fallback_icon();
        return $mapping[self::normalise_machine_name($machinename)] ?? $fallback;
    }

    /** Parse the intentionally low-level one-entry-per-line mapping format. */
    public static function parse_mapping(string $mapping): array {
        $result = [];
        foreach (preg_split('/\R/', $mapping) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }
            [$machine, $iconkey] = array_map('trim', explode('=', $line, 2));
            $machine = self::normalise_machine_name($machine);
            $iconkey = strtolower($iconkey);
            if ($machine !== '' && in_array($iconkey, icon_catalog::keys(), true)) {
                $result[$machine] = $iconkey;
            }
        }
        return $result;
    }

    /** Validate administrator input before saving it; never silently lose a misspelled mapping. */
    public static function validate_mapping(string $mapping): string {
        $seen = [];
        foreach (preg_split('/\R/', $mapping) ?: [] as $index => $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            $parts = explode('=', $line);
            $machine = self::normalise_machine_name(trim($parts[0]));
            if (count($parts) !== 2 || !preg_match('/^[a-z][a-z0-9_]*$/', $machine)
                    || !in_array(strtolower(trim($parts[1] ?? '')), icon_catalog::keys(), true)
                    || isset($seen[$machine])) {
                return get_string('mappinginvalidline', 'local_activityicons', $index + 1);
            }
            $seen[$machine] = true;
        }
        return '';
    }

    public static function default_mapping_text(): string {
        $lines = [];
        foreach (self::DEFAULT_MAPPING as $machine => $iconkey) {
            $lines[] = 'H5P.' . $machine . '=' . $iconkey;
        }
        return implode("\n", $lines);
    }

    public static function configured_mapping(): array {
        $configured = get_config('local_activityicons', 'h5pmapping');
        if ($configured === false) {
            return self::DEFAULT_MAPPING;
        }
        return self::parse_mapping((string) $configured);
    }

    /** Update only the selected exact types; an empty icon restores the configured fallback. */
    public static function assign_types(array $machines, string $iconkey): void {
        $lock = \core\lock\lock_config::get_lock_factory('local_activityicons')->get_lock('poolmetadata', 10);
        if (!$lock) {
            throw new \moodle_exception('locktimeout', 'error');
        }
        try {
            pool::reset_cache();
            \cache::make('core', 'config')->delete('local_activityicons');
            if ($iconkey !== '') {
                icon_catalog::normalise($iconkey);
            }
            $mapping = self::configured_mapping();
            foreach ($machines as $machine) {
                $name = self::normalise_machine_name($machine);
                if (!preg_match('/^[a-z][a-z0-9_]*$/', $name)) {
                    throw new \invalid_parameter_exception(get_string('invalidmachine', 'local_activityicons'));
                }
                if ($iconkey === '') {
                    unset($mapping[$name]);
                } else {
                    $mapping[$name] = $iconkey;
                }
            }
            $lines = [];
            foreach ($mapping as $name => $key) {
                $lines[] = 'H5P.' . $name . '=' . $key;
            }
            set_config('h5pmapping', implode("\n", $lines), 'local_activityicons');
        } finally {
            $lock->release();
        }
    }

    private static function fallback_icon(): ?string {
        $fallback = get_config('local_activityicons', 'h5pfallback');
        if ($fallback === false || $fallback === '') {
            return 'h5p';
        }
        if ($fallback === 'default') {
            return null;
        }
        return in_array($fallback, icon_catalog::keys(), true) ? $fallback : 'h5p';
    }

    private static function normalise_machine_name(string $machinename): string {
        $name = strtolower(trim($machinename));
        return str_starts_with($name, 'h5p.') ? substr($name, 4) : $name;
    }
}
