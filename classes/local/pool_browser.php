<?php
// This file is part of Moodle - https://moodle.org/
/**
 * This file is part of a 108design source-available software product.
 *
 * Use and modification are permitted only under the Software License included
 * with this distribution. See LICENSE.md for the full terms.
 *
 * File documentation for classes/local/pool_browser.php.
 *
 * @package   local_activityicons
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license   See LICENSE.md for the full terms.
 */

namespace local_activityicons\local;

/** The icons and allowed operations for one administration pool. */
final class pool_browser {
    public readonly string $label;
    public readonly ?int $groupid;
    public readonly array $entries;

    public function __construct(public readonly string $key, \renderer_base $renderer) {
        $pools = icon_catalog::pools();
        if (!isset($pools[$key])) {
            throw new \moodle_exception('groupmissing', 'local_activityicons', self::url());
        }
        $this->label = $key === 'custom' ? get_string('ownuploads', 'local_activityicons') : $pools[$key];
        $this->groupid = $key === 'custom' ? 0 : (str_starts_with($key, 'custom-') ? (int) substr($key, 7) : null);
        $entries = [];
        $uploads = pool::records();
        $used = $this->groupid !== null ? pool::used_keys() : [];
        $developer = pool::developer_mode_enabled();
        $canmove = (bool) $this->destinations('move');
        $cancopy = (bool) $this->destinations('copy');
        foreach (icon_catalog::client_entries($renderer) as $entry) {
            $primary = $entry['pool'] === $key;
            if (!$primary && !in_array($key, $entry['additionalPools'], true)) {
                continue;
            }
            $entry['upload'] = $primary ? ($uploads[$entry['key']] ?? null) : null;
            $entry['used'] = isset($used[$entry['key']]);
            $entry['actions'] = [];
            if ($cancopy) {
                $entry['actions'][] = 'copy';
            }
            if (!$primary) {
                $entry['actions'][] = 'remove';
            } else if ($entry['upload']) {
                if ($canmove) {
                    $entry['actions'][] = 'move';
                }
                $entry['actions'][] = 'rename';
                if (!$entry['used'] || $developer) {
                    $entry['actions'][] = 'delete';
                }
            }
            $entries[$entry['key']] = $entry;
        }
        $this->entries = $entries;
    }

    /** Whether developer mode enables deletion of a used primary upload on this page. */
    public function has_used_uploads(): bool {
        foreach ($this->entries as $entry) {
            if ($entry['upload'] && $entry['used']) {
                return true;
            }
        }
        return false;
    }

    /** All initially available operations. The selection narrows this list. */
    public function actions(): array {
        $options = [
            'move' => 'moveselectedicons', 'copy' => 'copyselectedicons', 'rename' => 'savelabels',
            'remove' => 'removefrompool', 'delete' => 'deleteiconsselected',
        ];
        $available = [];
        foreach ($this->entries as $entry) {
            $available = array_merge($available, $entry['actions']);
        }
        return array_intersect_key($options, array_fill_keys($available, true));
    }

    /** Validate all selected keys against the current source pool and operation. */
    public function selection(array $keys, string $action): array {
        $keys = array_unique($keys);
        if (!$keys || count($keys) > 500) {
            throw new \invalid_parameter_exception(get_string('selecticonsfirst', 'local_activityicons'));
        }
        $selected = [];
        foreach ($keys as $key) {
            if (!isset($this->entries[$key]) || !in_array($action, $this->entries[$key]['actions'], true)) {
                throw new \invalid_parameter_exception(get_string('poolactioninvalid', 'local_activityicons'));
            }
            $selected[$key] = $this->entries[$key];
        }
        return $selected;
    }

    /** A copy goes into a named pool, a move may also return uploads to their default pool. */
    public function destinations(string $action): array {
        $destinations = pool_groups::options();
        $destinations[0] = get_string('ownuploads', 'local_activityicons');
        if ($action === 'copy') {
            unset($destinations[0]);
        }
        if ($this->groupid !== null) {
            unset($destinations[$this->groupid]);
        }
        return $destinations;
    }

    public static function url(string $key = ''): \moodle_url {
        return new \moodle_url('/local/activityicons/pool.php', $key === '' ? [] : ['pool' => $key]);
    }
}
