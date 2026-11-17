<?php
// This file is part of Moodle - https://moodle.org/
/**
 * This file is part of a 108design source-available software product.
 *
 * Use and modification are permitted only under the Software License included
 * with this distribution. See LICENSE.md for the full terms.
 *
 * File documentation for tests/pool_groups_test.php.
 *
 * @package   local_activityicons
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license   See LICENSE.md for the full terms.
 */
namespace local_activityicons;

use local_activityicons\local\h5p_resolver;
use local_activityicons\local\icon_catalog;
use local_activityicons\local\pool;
use local_activityicons\local\pool_groups;
use local_activityicons\local\repository;

/** Cover site groups and the developer-mode deletion boundary. */
final class pool_groups_test extends \advanced_testcase {
    private const SVG = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M1 2L20 20"/></svg>';

    public function test_groups_keep_icon_identity_and_assignments(): void {
        global $PAGE;

        $this->resetAfterTest();
        $this->setAdminUser();
        $PAGE->set_context(\context_system::instance());
        $first = pool_groups::save(0, 'PDF Workspace', ['phosphor-light-highlighter']);
        $second = pool_groups::save(0, 'Training', ['phosphor-light-highlighter']);
        $asset = pool::store_batch([['Annotation', 'sample.svg', self::SVG]], $first)[0];
        $reused = pool::store_batch([['Duplicate', 'copy.svg', self::SVG]], $second)[0];
        $this->assertSame((int) $asset->id, (int) $reused->id);
        $this->assertSame($first, (int) $reused->groupid);
        $course = $this->getDataGenerator()->create_course();
        $page = $this->getDataGenerator()->create_module('page', ['course' => $course->id]);
        $repository = new repository();
        $selection = repository::ICON_PREFIX . $asset->iconkey;
        $repository->set_selection($course->id, $page->cmid, $selection);

        pool_groups::reorder($second, -1);
        $this->assertSame([$second, $first], array_keys(pool_groups::records()));
        $this->assertSame($second, pool_groups::save($second, 'Workshop', ['phosphor-light-highlighter']));
        pool_groups::move_uploads([$asset->id], $second);
        $this->assertSame($second, (int) pool::records()[$asset->iconkey]->groupid);
        $groupkey = pool_groups::picker_key($second);
        $entries = array_column(icon_catalog::client_entries($PAGE->get_renderer('core')), null, 'key');
        $this->assertSame($groupkey, $entries[$asset->iconkey]['pool']);
        $this->assertContains($groupkey, $entries['phosphor-light-highlighter']['additionalPools']);
        $this->assertSame('pdf', $entries['phosphor-light-highlighter']['pool']);

        pool_groups::delete($second);
        $this->assertNull(pool::records()[$asset->iconkey]->groupid);
        $this->assertNotNull(pool::file($asset->id));
        $this->assertSame($selection, $repository->get_selection($page->cmid));
        $this->assertContains(pool_groups::picker_key($first), pool_groups::memberships()['phosphor-light-highlighter']);
        $this->assertNotContains($groupkey, pool_groups::memberships()['phosphor-light-highlighter']);
    }

    public function test_force_deletion_checks_usage_and_resets_all_reference_types(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $asset = pool::store('Annotation', 'sample.svg', self::SVG);
        $course = $this->getDataGenerator()->create_course();
        $page = $this->getDataGenerator()->create_module('page', ['course' => $course->id]);
        $repository = new repository();
        $repository->set_selection($course->id, $page->cmid, repository::ICON_PREFIX . $asset->iconkey);
        $old = "# keep this comment\nH5P.Video={$asset->iconkey}\nH5P.QuestionSet=quiz";
        set_config('h5pmapping', $old, 'local_activityicons');
        set_config('h5pfallback', 'image', 'local_activityicons');
        $records = [(int) $asset->id => $asset];
        $usage = pool::deletion_usage($records);
        $this->assertCount(1, $usage[$asset->id]['assignments']);
        $this->assertSame(['H5P.video'], $usage[$asset->id]['types']);
        $this->assertFalse($usage[$asset->id]['fallback']);
        $this->assertSame(['deleted' => 0, 'kept' => 1], pool::delete_unused_batch([$asset->id]));
        try {
            pool::delete_unused_batch([$asset->id], true, pool::usage_hash($usage));
            $this->fail('Developer Mode is disabled by default.');
        } catch (\moodle_exception $exception) {
            $this->assertSame('developermodedisabled', $exception->errorcode);
        }
        set_config('developermode', 1, 'local_activityicons');
        set_config('h5pfallback', $asset->iconkey, 'local_activityicons');
        try {
            pool::delete_unused_batch([$asset->id], true, pool::usage_hash($usage));
            $this->fail('The changed usage must be confirmed again.');
        } catch (\moodle_exception $exception) {
            $this->assertSame('deleteusagechanged', $exception->errorcode);
        }
        $this->assertNotNull(pool::file($asset->id));
        $usage = pool::deletion_usage($records);
        $this->assertTrue($usage[$asset->id]['fallback']);
        $this->assertSame(['deleted' => 1, 'kept' => 0], pool::delete_unused_batch([$asset->id], true, pool::usage_hash($usage)));
        $this->assertSame(repository::SELECTION_DEFAULT, $repository->get_selection($page->cmid));
        $this->assertNull($repository->get_record($page->cmid)->iconkey);
        $this->assertSame("# keep this comment\nH5P.Video=default\nH5P.QuestionSet=quiz",
            get_config('local_activityicons', 'h5pmapping'));
        $this->assertNull(h5p_resolver::icon_for_machine_name('H5P.Video', null, 'image'));
        $this->assertSame('quiz', h5p_resolver::icon_for_machine_name('H5P.QuestionSet'));
        $this->assertSame('default', get_config('local_activityicons', 'h5pfallback'));
        $this->assertNull(pool::file($asset->id));
    }

    public function test_pool_manager_cannot_force_delete_an_icon(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        $asset = pool::store('Annotation', 'sample.svg', self::SVG);
        set_config('developermode', 1, 'local_activityicons');
        set_config('h5pfallback', $asset->iconkey, 'local_activityicons');
        $manager = $this->getDataGenerator()->create_user();
        $role = $DB->get_field('role', 'id', ['shortname' => 'manager'], MUST_EXIST);
        role_assign($role, $manager->id, \context_system::instance()->id);
        $this->setUser($manager);
        $this->assertTrue(has_capability('local/activityicons:managepool', \context_system::instance()));
        $this->assertFalse(pool::developer_mode_enabled());
        $this->expectException(\required_capability_exception::class);
        pool::delete_unused_batch([$asset->id], true, pool::usage_hash(pool::deletion_usage([$asset])));
    }
}
