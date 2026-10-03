<?php
namespace local_activityicons;

/** Guard the Moodle APIs on which the cross-version design depends. */
final class compatibility_test extends \advanced_testcase {
    public function test_required_moodle_apis_exist(): void {
        $this->assertTrue(class_exists(\core\hook\output\before_http_headers::class));
        $this->assertTrue(method_exists(\cm_info::class, 'set_icon_url'));
        $this->assertTrue(method_exists(\course_modinfo::class, 'purge_course_module_cache'));
        $this->assertTrue(method_exists(\core_h5p\api::class, 'get_content_from_pathnamehash'));
        $this->assertTrue(method_exists(\core_h5p\api::class, 'get_library'));
    }

    public function test_every_catalog_entry_has_an_svg(): void {
        global $CFG;
        foreach (\local_activityicons\local\icon_catalog::keys() as $key) {
            $this->assertFileExists($CFG->dirroot . '/local/activityicons/pix/pool/' . $key . '.svg');
        }
    }

    public function test_ajax_services_initialise_page_context_for_renderer(): void {
        global $PAGE;

        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $activity = $this->getDataGenerator()->create_module('page', ['course' => $course->id]);

        $PAGE = new \moodle_page();
        $response = \local_activityicons\external\get_icons::execute([(int) $activity->cmid]);
        $this->assertSame([], json_decode($response['descriptorsjson'], true, 512, JSON_THROW_ON_ERROR));

        $PAGE = new \moodle_page();
        $response = \local_activityicons\external\set_icon::execute(
            (int) $course->id,
            (int) $activity->cmid,
            'icon:video'
        );
        $descriptor = json_decode($response['descriptorjson'], true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('custom', $descriptor['kind']);
        $this->assertSame('video', $descriptor['key']);
        $this->assertSame('icon:video', $descriptor['selection']);
    }
}
