<?php
namespace local_activityicons\privacy;

/** Activity icon assignments contain no personal data. */
final class provider implements \core_privacy\local\metadata\null_provider {
    public static function get_reason(): string {
        return 'privacy:metadata';
    }
}
