<?php
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

    public static function keys(): array {
        return array_merge(array_keys(self::ICONS), array_keys(pool::records()));
    }

    public static function normalise(string $iconkey): string {
        $iconkey = trim($iconkey);
        if (!in_array($iconkey, self::keys(), true)) {
            throw new invalid_parameter_exception(get_string('invalidselection', 'local_activityicons'));
        }
        return $iconkey;
    }

    /** Return translated picker entries including Moodle-resolved, theme-overridable URLs. */
    public static function client_entries(renderer_base $output): array {
        $result = [];
        foreach (self::ICONS as $key => $definition) {
            $result[] = [
                'key' => $key,
                'selection' => repository::ICON_PREFIX . $key,
                'label' => get_string($definition['label'], 'local_activityicons'),
                'search' => $definition['search'],
                'url' => self::url($key, $output)->out(false),
            ];
        }
        foreach (pool::records() as $key => $record) {
            $url = pool::url($record);
            if ($url) {
                $result[] = ['key' => $key, 'selection' => repository::ICON_PREFIX . $key,
                    'label' => $record->label, 'search' => $record->label, 'url' => $url->out(false)];
            }
        }
        return $result;
    }

    public static function form_options(): array {
        $options = [];
        foreach (self::ICONS as $key => $definition) {
            $options[repository::ICON_PREFIX . $key] = get_string($definition['label'], 'local_activityicons');
        }
        foreach (pool::records() as $key => $record) {
            $options[repository::ICON_PREFIX . $key] = $record->label;
        }
        return $options;
    }

    /** Return icon-key options for settings that store a bare catalog key. */
    public static function key_options(): array {
        $options = [];
        foreach (self::ICONS as $key => $definition) {
            $options[$key] = get_string($definition['label'], 'local_activityicons');
        }
        foreach (pool::records() as $key => $record) {
            $options[$key] = $record->label;
        }
        return $options;
    }

    public static function url(string $iconkey, renderer_base $output): \moodle_url {
        $iconkey = self::normalise($iconkey);
        $custom = pool::records()[$iconkey] ?? null;
        if ($custom && ($url = pool::url($custom))) {
            return $url;
        }
        if ($custom) {
            $iconkey = 'h5p';
        }
        $url = $output->image_url('pool/' . $iconkey, 'local_activityicons');
        $url->param('filtericon', 1);
        return $url;
    }
}
