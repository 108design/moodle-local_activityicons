<?php
// This file is part of Moodle - https://moodle.org/
/**
 * This file is part of a 108design source-available software product.
 *
 * Use and modification are permitted only under the Software License included
 * with this distribution. See LICENSE.md for the full terms.
 *
 * File documentation for classes/local/admin_page.php.
 *
 * @package   local_activityicons
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license   See LICENSE.md for the full terms.
 */
namespace local_activityicons\local;

/** Shared tab navigation for the Activity Icons administration pages. */
final class admin_page {
    public static function prepare(\moodle_url $url): void {
        global $OUTPUT, $PAGE;
        admin_externalpage_setup('local_activityicons', '', [], $url);
        context_help::prepare();
        $logo = \html_writer::empty_tag('img', [
            'src' => $OUTPUT->image_url('icon', 'local_activityicons'),
            'alt' => '', 'aria-hidden' => 'true', 'width' => 32, 'height' => 32,
            'class' => 'local-activityicons-admin-logo',
        ]);
        $PAGE->set_heading(\html_writer::span($logo . \html_writer::span(s(get_string('pluginname', 'local_activityicons'))),
            'local-activityicons-admin-heading'), false);
    }

    /** The active parent remains linked from forms and confirmations. */
    public static function tabs(string $active): string {
        $tabs = ['pool' => 'tabpool', 'mapping' => 'tabmapping'];
        if (has_capability('moodle/site:config', \context_system::instance())) {
            $tabs['preferences'] = 'settings';
        }
        $items = '';
        foreach ($tabs as $path => $key) {
            $selected = $path === $active;
            $items .= \html_writer::tag('li', \html_writer::link(new \moodle_url("/local/activityicons/{$path}.php"),
                get_string($key, $key === 'settings' ? 'moodle' : 'local_activityicons'), [
                    'class' => 'nav-link' . ($selected ? ' active' : ''),
                    'aria-current' => $selected ? 'page' : null,
                ]), ['class' => 'nav-item']);
        }
        return \html_writer::tag('nav', \html_writer::tag('ul', $items, ['class' => 'nav nav-tabs flex-wrap mb-4']),
            ['aria-label' => get_string('pluginname', 'local_activityicons')]);
    }
}
