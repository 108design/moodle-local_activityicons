<?php
// Copyright (C) 2026 Andreas Giesen. See LICENSE.md.
namespace local_activityicons\local;

defined('MOODLE_INTERNAL') || die();

/** Contextual settings help, following the standalone Frontpage panel contract. */
final class context_help {
    private const TOPICS = ['overview', 'pool', 'mapping', 'behaviour'];
    private static bool $prepared = false;

    /** Register only the permitted settings page before its header is rendered. */
    public static function prepare(): void {
        global $CFG, $PAGE;
        if ($PAGE->url->get_path() !== (new \moodle_url('/local/activityicons/pool.php'))->get_path()) {
            throw new \coding_exception('Unknown Activity Icons help page.');
        }
        require_capability('local/activityicons:managepool', \context_system::instance());
        self::$prepared = true;
        $PAGE->requires->css(new \moodle_url('/local/activityicons/styles/context_help.css', [
            'rev' => get_config('local_activityicons', 'version'),
        ]));
        $module = (int)$CFG->version < 2025041400 ? 'context_help_legacy' : 'context_help';
        $PAGE->requires->js_call_amd('local_activityicons/context_help_form', 'init');
        $PAGE->requires->js_call_amd('local_activityicons/' . $module, 'init', [[
            'topic' => optional_param('activityiconshelp', '', PARAM_ALPHA),
        ]]);
    }

    /** Native popover with concise guidance and a direct link to the extended topic. */
    public static function icon(string $key): string {
        global $CFG, $PAGE;
        if (!in_array($key, self::TOPICS, true)) {
            throw new \coding_exception('Unknown Activity Icons help topic.');
        }
        $url = new \moodle_url($PAGE->url);
        $url->param('activityiconshelp', $key);
        $help = new \help_icon($key, 'local_activityicons', $url->out(false));
        $renderer = $PAGE->get_renderer('core');
        $data = (array)$help->export_for_template($renderer);
        $data['completedoclink'] = \html_writer::link($url, get_string('settingshelpmore', 'local_activityicons'));
        $data['legacy'] = (int)$CFG->version < 2025041400;
        $data['infoicon'] = (int)$CFG->version < 2026100500 ? 'i/info' : 'i/circleinfo';
        return $renderer->render_from_template('local_activityicons/settings_help_icon', $data);
    }

    /** Always available in the native Topnav user area on the registered page. */
    public static function navbar(\renderer_base $renderer): string {
        global $CFG;
        if (!self::$prepared) {
            return '';
        }
        $label = get_string('settingshelp', 'local_activityicons');
        return \html_writer::tag('button', $renderer->pix_icon(
            (int)$CFG->version < 2026100500 ? 'i/info' : 'i/circleinfo', ''), [
                'type' => 'button', 'class' => 'nav-link icon-no-margin local-activityicons-help-navbar',
                'data-activityicons-help-open' => '', 'aria-label' => $label, 'title' => $label,
                'aria-controls' => 'local-activityicons-context-help', 'aria-expanded' => 'false',
            ]);
    }

    /** Same header, topic chooser and article layout as the Frontpage reference. */
    public static function render(): string {
        global $PAGE;
        if (!self::$prepared) {
            return '';
        }
        $title = get_string('settingshelp', 'local_activityicons');
        $close = \html_writer::tag('button', \html_writer::span('&#215;', '', ['aria-hidden' => 'true']), [
            'type' => 'button', 'class' => 'btn btn-secondary btn-sm local-activityicons-help-close',
            'aria-label' => get_string('closebuttontitle', 'moodle'), 'data-activityicons-help-close' => '1',
        ]);
        $options = '';
        $articles = '';
        foreach (self::TOPICS as $topic) {
            $label = get_string('settingshelp_' . $topic, 'local_activityicons');
            $options .= \html_writer::tag('option', $label, ['value' => $topic]);
            $articles .= \html_writer::tag('article', \html_writer::tag('h3', $label, ['class' => 'h5', 'tabindex' => '-1'])
                . get_string('settingshelp_' . $topic . '_article', 'local_activityicons'), [
                    'data-activityicons-help-article' => $topic, 'hidden' => 'hidden',
                ]);
        }
        $choice = \html_writer::tag('label', get_string('contexthelptopics', 'local_activityicons'), [
            'for' => 'local-activityicons-help-topic', 'class' => 'form-label',
        ]) . \html_writer::tag('select', $options, [
            'id' => 'local-activityicons-help-topic', 'class' => 'form-select custom-select',
        ]);
        // Shared help-panel identity: no link or action; close remains beside the topic selector.
        $brand = \html_writer::tag('span', \html_writer::empty_tag('img', [
            'src' => $PAGE->get_renderer('core')->image_url('helpbrand', 'local_activityicons')->out(false),
            'alt' => '', 'width' => 25, 'height' => 24,
        ]), [
            'class' => 'local-activityicons-help-brand', 'role' => 'img', 'aria-label' => '108design',
            'title' => '108design', 'tabindex' => '0', 'data-toggle' => 'tooltip', 'data-bs-toggle' => 'tooltip',
        ]);
        $header = \html_writer::tag('header', \html_writer::tag('h2', $title, [
            'id' => 'local-activityicons-context-help-title', 'class' => 'sr-only visually-hidden', 'tabindex' => '-1',
        ]) . \html_writer::div($choice, 'local-activityicons-help-choices') . $brand . $close,
            ['class' => 'local-activityicons-help-header local-activityicons-help-header-branded']);
        return \html_writer::tag('aside', $header
            . \html_writer::div($articles, 'local-activityicons-help-content', ['tabindex' => '0']), [
                'id' => 'local-activityicons-context-help', 'class' => 'local-activityicons-context-help',
                'aria-labelledby' => 'local-activityicons-context-help-title', 'hidden' => 'hidden',
                'data-activityicons-help-url' => $PAGE->url->out(false),
            ]);
    }
}
