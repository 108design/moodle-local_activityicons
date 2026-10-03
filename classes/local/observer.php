<?php
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
