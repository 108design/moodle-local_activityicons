<?php
// This file is part of Moodle - https://moodle.org/
/**
 * This file is part of a 108design source-available software product.
 *
 * Use and modification are permitted only under the Software License included
 * with this distribution. See LICENSE.md for the full terms.
 *
 * File documentation for classes/output/pool_browser.php.
 *
 * @package   local_activityicons
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license   See LICENSE.md for the full terms.
 */

namespace local_activityicons\output;

use html_writer;
use local_activityicons\local\context_help;
use local_activityicons\local\icon_catalog;
use local_activityicons\local\pool_browser as pool_view;
use local_activityicons\local\pool_groups;
use moodle_url;

/** Administration cards and pool content views. */
final class pool_browser {
    public function __construct(private readonly \renderer_base $output) {
    }

    /** Show the upload pools first and the built-in catalogue below. */
    public function overview(): string {
        $members = [];
        foreach (icon_catalog::client_entries($this->output) as $entry) {
            foreach ([$entry['pool'], ...$entry['additionalPools']] as $key) {
                $members[$key][$entry['key']] = $entry;
            }
        }
        $custom = [['custom', get_string('ownuploads', 'local_activityicons'), '']];
        $groupurl = new moodle_url('/local/activityicons/groups.php');
        foreach (pool_groups::records() as $group) {
            $key = pool_groups::picker_key((int) $group->id);
            $actions = $this->action('i/edit', get_string('poolmetadatanamed', 'local_activityicons', $group->name),
                new moodle_url($groupurl, ['action' => 'edit', 'id' => $group->id]));
            $actions .= $this->action('i/delete', get_string('deletegroupnamed', 'local_activityicons', $group->name),
                new moodle_url($groupurl, ['action' => 'confirmdelete', 'id' => $group->id]));
            $custom[] = [$key, $group->name, $actions, $group->referenceicon ?? ''];
        }
        $builtins = [];
        foreach (icon_catalog::pools() as $key => $label) {
            if ($key !== 'custom' && !str_starts_with($key, 'custom-')) {
                $builtins[] = [$key, $label, ''];
            }
        }
        $add = html_writer::link(new moodle_url($groupurl, ['action' => 'edit']),
            $this->output->pix_icon('t/add', '', 'moodle', ['class' => 'icon m-0']) .
            get_string('addgroup', 'local_activityicons'),
            ['class' => 'btn btn-primary d-inline-flex align-items-center gap-2 ms-auto ml-auto']);
        $ownheading = $this->output->heading(get_string('ownpools', 'local_activityicons'), 2, 'mb-0');
        $toolbar = html_writer::div($ownheading . $add, 'd-flex flex-wrap align-items-center gap-2 mb-3');
        $info = html_writer::tag('p', get_string('overview_help', 'local_activityicons') . ' ' . context_help::icon('groups'));
        $builtinheading = $this->output->heading(get_string('cataloguepools', 'local_activityicons'), 2);
        return html_writer::tag('section', $toolbar . $info . $this->grid($custom, $members, 'ownpools'),
            ['class' => 'mb-4']) . html_writer::tag('section', $builtinheading . $this->grid($builtins, $members, 'cataloguepools'));
    }

    /** Display icon cards and a shared action bar. */
    public function contents(pool_view $view): string {
        if (!$view->entries) {
            return html_writer::tag('p', get_string('poolisempty', 'local_activityicons') . ' ' . context_help::icon('pool'));
        }
        $actions = $view->actions();
        $component = 'local_activityicons';
        $help = get_string($view->groupid === null ? 'catalogueinstructions' : 'poolbulkintro', $component);
        if (!$actions) {
            $help = get_string('creategroupforcopy', $component);
        }
        $html = html_writer::tag('p', $help . ' ' . context_help::icon('pool'), ['class' => 'mb-3']);
        $cards = '';
        foreach ($view->entries as $entry) {
            $cards .= $this->card($entry);
        }
        $grid = html_writer::div($cards, 'local-activityicons-pool-grid');
        if (!$actions) {
            return $html . $grid;
        }
        $url = new moodle_url('/local/activityicons/transfer.php');
        $options = ['' => get_string('choosepoolaction', $component)];
        foreach ($actions as $key => $label) {
            $options[$key] = get_string($label, $component);
        }
        $select = html_writer::select($options, 'action', '', false, [
            'id' => 'activityicons-pool-action', 'class' => 'custom-select form-select w-auto', 'required' => 'required',
        ]);
        $bar = html_writer::label(get_string('selectedpoolaction', $component), 'activityicons-pool-action', false,
            ['class' => 'sr-only visually-hidden']) . $select;
        $bar .= html_writer::tag('button', get_string('runpoolaction', $component), ['class' => 'btn btn-primary', 'type' => 'submit']);
        $html .= html_writer::tag('form', $this->hidden('sesskey', sesskey()) . $this->hidden('source', $view->key) .
            $grid . html_writer::div($bar, 'd-flex flex-wrap align-items-center gap-2 mt-3 local-activityicons-pool-actions'), [
                'method' => 'post', 'action' => $url->out(false), 'data-activityicons-pool-form' => '1',
            ]);
        return $html;
    }

    private function card(array $entry): string {
        $component = 'local_activityicons';
        $label = $entry['label'];
        $checkbox = '';
        if ($entry['actions']) {
            $checkbox = html_writer::empty_tag('input', [
                'type' => 'checkbox', 'name' => 'icons[]', 'value' => $entry['key'],
                'aria-label' => get_string('selecticonaction', $component, $label),
                'data-pool-actions' => implode(' ', $entry['actions']),
                'class' => 'form-check-input local-activityicons-pool-check',
            ]);
        }
        $icon = html_writer::empty_tag('img', [
            'src' => $entry['url'], 'alt' => '', 'width' => 32, 'height' => 32, 'loading' => 'lazy',
            'class' => 'local-activityicons-preview',
        ]);
        $name = $entry['upload'] ? html_writer::empty_tag('input', [
            'type' => 'text', 'name' => 'labels[' . $entry['key'] . ']', 'value' => $label, 'maxlength' => 100,
            'class' => 'form-control', 'aria-label' => get_string('iconlabel', $component) . ': ' . $label,
        ]) : html_writer::span(s($label));
        $used = $entry['used'] && $entry['upload'] ? html_writer::span(get_string('iconcurrentlyused', $component), 'small') : '';
        return html_writer::div($checkbox . $icon . $name . $used, 'local-activityicons-pool-card', [
            'data-icon-key' => $entry['key'],
        ]);
    }

    /** The saved reference takes precedence while it remains in the displayed pool. */
    private function grid(array $pools, array $members, string $name): string {
        $cards = '';
        foreach ($pools as $item) {
            [$key, $label, $actions] = $item;
            $icons = $members[$key] ?? [];
            $reference = $icons[$item[3] ?? ''] ?? reset($icons);
            $preview = $reference ? html_writer::empty_tag('img', [
                'src' => $reference['url'], 'alt' => '', 'width' => 48, 'height' => 48,
                'loading' => 'lazy', 'class' => 'local-activityicons-pool-reference',
                'data-icon-key' => $reference['key'],
            ]) : $this->output->pix_icon('i/folder', '', 'moodle', ['class' => 'local-activityicons-pool-reference']);
            $title = html_writer::tag('h3', s($label), ['class' => 'card-header h6 text-center mb-0']);
            $count = html_writer::span(get_string('poolcardcount', 'local_activityicons', count($icons)), 'small text-muted');
            $body = html_writer::div($preview . $count,
                'card-body d-flex flex-column align-items-center justify-content-center gap-2');
            $view = html_writer::link(pool_view::url($key), $title . $body, [
                'class' => 'local-activityicons-pool-view d-flex flex-column flex-grow-1 text-reset',
                'aria-label' => get_string('openpoolnamed', 'local_activityicons', $label),
            ]);
            $footer = html_writer::div($actions, 'card-footer d-flex flex-wrap align-items-center justify-content-center gap-1');
            $cards .= html_writer::tag('article', $view . $footer, ['class' => 'card', 'data-pool-key' => $key]);
        }
        return html_writer::div($cards, 'local-activityicons-pool-overview',
            ['role' => 'group', 'aria-label' => get_string($name, 'local_activityicons')]);
    }

    /** Match native Moodle icon actions with accessible labels and Bootstrap tooltips. */
    private function action(string $icon, string $label, moodle_url $url): string {
        $attrs = [
            'class' => 'btn btn-icon icon-no-margin align-middle',
            'title' => $label, 'aria-label' => $label, 'data-toggle' => 'tooltip', 'data-bs-toggle' => 'tooltip',
        ];
        return html_writer::link($url, $this->output->pix_icon($icon, ''), $attrs);
    }

    private function hidden(string $name, string $value): string {
        return html_writer::empty_tag('input', ['type' => 'hidden', 'name' => $name, 'value' => $value]);
    }
}
