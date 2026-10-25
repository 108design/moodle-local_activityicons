<?php
/**
 * This file is part of a 108design source-available software product.
 *
 * Use and modification are permitted only under the Software License included
 * with this distribution. See LICENSE.md for the full terms.
 *
 * File documentation for classes/local/observer.php.
 *
 * @package   local_activityicons
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license   See LICENSE.md for the full terms.
 */
namespace local_activityicons\local;

/** Keep assignments in sync when Moodle deletes their owner. */
final class observer {
    public static function course_module_deleted(\core\event\course_module_deleted $event): void {
        (new repository())->delete_course_module((int) $event->contextinstanceid);
    }

    public static function course_deleted(\core\event\course_deleted $event): void {
        (new repository())->delete_course((int) $event->objectid);
    }
}
