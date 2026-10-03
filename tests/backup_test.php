<?php
namespace local_activityicons;

use local_activityicons\local\pool;
use local_activityicons\local\repository;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/backup/tests/backup_restore_base_testcase.php');

/** Exercise the real course backup pipeline, including a missing destination asset. */
final class backup_test extends \core_backup_backup_restore_base_testcase {
    public function test_custom_file_and_course_policy_survive_restore(): void {
        global $DB, $CFG, $USER;
        $course = $this->getDataGenerator()->create_course();
        $destination = $this->getDataGenerator()->create_course();
        $page = $this->getDataGenerator()->create_module('page', ['course' => $course->id]);
        $icon = pool::store('Backup icon', 'test.svg',
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/></svg>');
        $repository = new repository();
        $repository->set_selection($course->id, $page->cmid, 'icon:' . $icon->iconkey);
        $repository->set_course_auto_mode($course->id, 'enabled');
        $backupid = $this->perform_backup($course);
        $coursexml = file_get_contents($CFG->tempdir . '/backup/' . $backupid . '/course/course.xml');
        $this->assertStringContainsString($icon->iconkey, $coursexml, 'Custom icon must be in course XML');
        $this->assertStringContainsString('<assignment ', $coursexml, 'Assignment must be in course XML');
        $this->assertStringContainsString('<poolicon ', $coursexml, 'Pool metadata must be in course XML');
        $this->assertStringContainsString('<component>local_activityicons</component>',
            file_get_contents($CFG->tempdir . '/backup/' . $backupid . '/files.xml'));
        // Simulate another site: neither the pool row nor its file exists there.
        get_file_storage()->delete_area_files(\context_system::instance()->id, 'local_activityicons', 'pool', $icon->id);
        $DB->delete_records(pool::TABLE, ['id' => $icon->id]);
        pool::reset_cache();
        $restore = new \restore_controller($backupid, $destination->id, \backup::INTERACTIVE_NO,
            \backup::MODE_GENERAL, $USER->id, \backup::TARGET_EXISTING_ADDING);
        $restore->get_plan()->get_setting('overwrite_conf')->set_value(true);
        $restore->execute_precheck();
        $restore->execute_plan();
        $restore->destroy();
        $this->assertArrayHasKey($icon->iconkey, pool::records(), 'Restore must promote the validated pool asset');
        $this->assertSame('enabled', $repository->get_course_auto_mode($destination->id));
        $records = $repository->records_for_course($destination->id);
        $this->assertCount(1, $records);
        $restored = reset($records);
        $this->assertNotEquals($page->cmid, $restored->cmid);
        $this->assertSame($icon->iconkey, $restored->iconkey);
        $poolrecord = pool::records()[$restored->iconkey];
        $this->assertNotNull(pool::file($poolrecord->id));
        $this->assertSame('enabled', $repository->get_course_auto_mode($destination->id));
        $this->assertEmpty(get_file_storage()->get_area_files(\context_course::instance($destination->id)->id,
            'local_activityicons', 'pool', false, 'id', false));
    }

    public function test_merge_restores_activity_icons_without_overwriting_course_policy(): void {
        $course = $this->getDataGenerator()->create_course();
        $destination = $this->getDataGenerator()->create_course();
        $page = $this->getDataGenerator()->create_module('page', ['course' => $course->id]);
        $repository = new repository();
        $repository->set_selection($course->id, $page->cmid, 'icon:video');
        $repository->set_course_auto_mode($course->id, 'enabled');
        $repository->set_course_auto_mode($destination->id, 'disabled');
        $this->perform_restore($this->perform_backup($course), $destination);
        $records = $repository->records_for_course($destination->id);
        $this->assertCount(1, $records);
        $this->assertSame('video', reset($records)->iconkey);
        $this->assertSame('disabled', $repository->get_course_auto_mode($destination->id));
    }
}
