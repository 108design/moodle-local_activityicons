<?php
// This file is part of Moodle - https://moodle.org/
/**
 * This file is part of a 108design source-available software product.
 *
 * Use and modification are permitted only under the Software License included
 * with this distribution. See LICENSE.md for the full terms.
 *
 * File documentation for classes/local/icon_catalog.php.
 *
 * @package   local_activityicons
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license   See LICENSE.md for the full terms.
 */
namespace local_activityicons\local;

use invalid_parameter_exception;
use renderer_base;

/** Manifest and URL factory for trusted SVG icons shipped with the plugin. */
final class icon_catalog {
    private const ICONS = [
        'video' => ['label' => 'iconvideo', 'search' => 'video film movie play'],
        'quiz' => ['label' => 'iconquiz', 'search' => 'quiz test question assessment'],
        'presentation' => ['label' => 'iconpresentation', 'search' => 'presentation slides course'],
        'branching' => ['label' => 'iconbranching', 'search' => 'branching scenario decision path'],
        'audio' => ['label' => 'iconaudio', 'search' => 'audio sound podcast listen'],
        'image' => ['label' => 'iconimage', 'search' => 'image picture photo visual'],
        'game' => ['label' => 'icongame', 'search' => 'game memory play'],
        'h5p' => ['label' => 'iconh5p', 'search' => 'interactive h5p content fallback'],
    ];

    /** Group labels in picker order, independently of the stored icon keys. */
    public static function pools(): array {
        $pools = array_merge(['original'], self::phosphor_manifest()['pools'], ['custom']);
        $labels = [];
        foreach ($pools as $pool) {
            $labels[$pool] = get_string('pool' . $pool, 'local_activityicons');
        }
        foreach (pool_groups::records() as $group) {
            $label = $group->name;
            if (in_array($label, $labels, true)) {
                $label = get_string('customgroupdisplay', 'local_activityicons', $label);
            }
            // Ensure native optgroup labels stay distinct even if a custom name matches a bundled translation.
            while (in_array($label, $labels, true)) {
                $label .= ' (' . $group->id . ')';
            }
            $labels[pool_groups::picker_key((int) $group->id)] = $label;
        }
        return $labels;
    }

    /** Keys of the bundled symbols, which may be referenced by custom pools. */
    public static function bundled_keys(): array {
        return array_merge(array_keys(self::ICONS), array_keys(self::phosphor_manifest()['icons']));
    }

    public static function keys(): array {
        return array_merge(self::bundled_keys(), array_keys(pool::records()));
    }

    public static function normalise(string $iconkey): string {
        $iconkey = trim($iconkey);
        if (!isset(self::ICONS[$iconkey]) && !isset(self::phosphor_manifest()['icons'][$iconkey])
                && !isset(pool::records()[$iconkey])) {
            throw new invalid_parameter_exception(get_string('invalidselection', 'local_activityicons'));
        }
        return $iconkey;
    }

    /** Return picker entries with theme-aware URLs. The upload editor shows its original and editable entries. */
    public static function client_entries(renderer_base $output, bool $includephosphor = true): array {
        $result = [];
        $memberships = $includephosphor ? pool_groups::memberships() : [];
        foreach (self::entries($includephosphor) as $key => $definition) {
            $url = isset($definition['record']) ? pool::url($definition['record']) : self::bundled_url($key, $output);
            if ($url) {
                unset($definition['record']);
                $result[] = $definition + [
                    'key' => $key,
                    'additionalPools' => $memberships[$key] ?? [],
                    'selection' => repository::ICON_PREFIX . $key,
                    'url' => $url->out(false),
                ];
            }
        }
        return $result;
    }

    /** Provide native form optgroups, keeping selection tokens identical to the picker. */
    public static function form_groups(): array {
        $pools = self::pools();
        $groups = array_fill_keys(array_values($pools), []);
        $memberships = pool_groups::memberships();
        foreach (self::entries() as $key => $definition) {
            $group = $pools[$definition['pool']] ?? $pools['custom'];
            $groups[$group][repository::ICON_PREFIX . $key] = $definition['label'];
            foreach ($memberships[$key] ?? [] as $additional) {
                if (isset($pools[$additional])) {
                    $groups[$pools[$additional]][repository::ICON_PREFIX . $key] = $definition['label'];
                }
            }
        }
        return array_filter($groups);
    }

    /** Return icon-key options for settings that store a bare catalog key. */
    public static function key_options(): array {
        $options = [];
        foreach (self::entries() as $key => $definition) {
            $options[$key] = $definition['label'];
        }
        return $options;
    }

    public static function url(string $iconkey, renderer_base $output): \moodle_url {
        $iconkey = self::normalise($iconkey);
        $custom = pool::records()[$iconkey] ?? null;
        if ($custom) {
            return pool::url($custom) ?? self::bundled_url('h5p', $output);
        }
        return self::bundled_url($iconkey, $output);
    }

    /** Return labels and their original group for bundled icons. */
    private static function bundled_entries(bool $includephosphor = true): array {
        $entries = [];
        foreach (self::ICONS as $key => $definition) {
            $entries[$key] = [
                'label' => get_string($definition['label'], 'local_activityicons'),
                'pool' => 'original',
                'search' => $definition['search'],
            ];
        }
        if ($includephosphor) {
            foreach (self::phosphor_manifest()['icons'] as $key => $definition) {
                $entries[$key] = $definition + [
                    'label' => get_string(str_replace('-', '', $key), 'local_activityicons'),
                ];
            }
        }
        return $entries;
    }

    /** Return display metadata for shipped assets and uploads through the same catalog interface. */
    private static function entries(bool $includephosphor = true): array {
        $entries = self::bundled_entries($includephosphor);
        foreach (pool::records() as $key => $record) {
            $entries[$key] = [
                'label' => $record->label,
                'pool' => pool_groups::picker_key((int) ($record->groupid ?? 0)),
                'search' => $record->label,
                'record' => $record,
            ];
        }
        return $entries;
    }

    private static function bundled_url(string $key, renderer_base $output): \moodle_url {
        $url = $output->image_url('pool/' . $key, 'local_activityicons');
        $url->param('filtericon', 1);
        return $url;
    }

    /** Read only the versioned plugin manifest. Uploads have their own storage and validation. */
    private static function phosphor_manifest(): array {
        static $manifest = null;
        return $manifest ??= json_decode(
            file_get_contents(__DIR__ . '/../../resources/phosphor_light.json'),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
    }
}
