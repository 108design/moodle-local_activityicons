<?php
namespace local_activityicons;

use local_activityicons\local\h5p_resolver;

/** Unit coverage for stable, broad H5P icon categories. */
final class h5p_resolver_test extends \advanced_testcase {
    public function test_machine_name_mapping(): void {
        $this->resetAfterTest();
        unset_config('h5pmapping', 'local_activityicons');
        unset_config('h5pfallback', 'local_activityicons');
        $cases = [
            ['H5P.InteractiveVideo', 'video'],
            ['H5P.CoursePresentation', 'presentation'],
            ['H5P.BranchingScenario', 'branching'],
            ['H5P.AudioRecorder', 'audio'],
            ['H5P.ImageHotspots', 'image'],
            ['H5P.MemoryGame', 'game'],
            ['H5P.MultiChoice', 'quiz'],
            ['H5P.Column', 'h5p'],
        ];
        foreach ($cases as [$machine, $expected]) {
            $this->assertSame($expected, h5p_resolver::icon_for_machine_name($machine), $machine);
        }
    }

    public function test_mapping_is_exact_editable_and_has_configurable_fallback(): void {
        $this->resetAfterTest();
        set_config('h5pmapping', "# Custom pool mapping\nH5P.InteractiveVideo=quiz", 'local_activityicons');
        set_config('h5pfallback', 'default', 'local_activityicons');
        $this->assertSame('quiz', h5p_resolver::icon_for_machine_name('H5P.InteractiveVideo'));
        $this->assertNull(h5p_resolver::icon_for_machine_name('H5P.CustomInteractiveVideo'));
        $this->assertNull(h5p_resolver::icon_for_machine_name('H5P.CoursePresentation'));
        set_config('h5pfallback', 'h5p', 'local_activityicons');
        $this->assertSame('h5p', h5p_resolver::icon_for_machine_name('H5P.Column'));
        $this->assertSame('', h5p_resolver::validate_mapping('H5P.InteractiveVideo=video'));
        $this->assertNotSame('', h5p_resolver::validate_mapping('H5P.InteractiveVideo=missing'));
        $this->assertNotSame('', h5p_resolver::validate_mapping("H5P.Video=video\nh5p.video=quiz"));
        set_config('h5pmapping', '', 'local_activityicons');
        $this->assertSame('h5p', h5p_resolver::icon_for_machine_name('H5P.InteractiveVideo'));
    }

    public function test_supported_h5p_activity_modules(): void {
        $this->assertTrue(h5p_resolver::supports_module('h5pactivity'));
        $this->assertTrue(h5p_resolver::supports_module('hvp'));
        $this->assertFalse(h5p_resolver::supports_module('page'));
    }

    public function test_unopened_core_package_is_resolved_from_manifest_without_deployment(): void {
        global $DB, $USER;

        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $activity = $this->getDataGenerator()->create_module('h5pactivity', ['course' => $course->id]);
        $context = \context_module::instance((int) $activity->cmid);
        get_file_storage()->delete_area_files($context->id, 'mod_h5pactivity', 'package');
        $file = get_file_packer('application/zip')->archive_to_storage(
            ['h5p.json' => [json_encode(['mainLibrary' => 'H5P.InteractiveVideo'], JSON_THROW_ON_ERROR)]],
            $context->id,
            'mod_h5pactivity',
            'package',
            0,
            '/',
            'interactive-video.h5p',
            $USER->id,
            false
        );
        $this->assertInstanceOf(\stored_file::class, $file);
        $this->assertFalse($DB->record_exists('h5p', ['pathnamehash' => $file->get_pathnamehash()]));

        $cm = get_fast_modinfo($course)->get_cm((int) $activity->cmid);
        $this->assertSame('video', (new h5p_resolver())->resolve($cm));
        $this->assertFalse($DB->record_exists('h5p', ['pathnamehash' => $file->get_pathnamehash()]));
    }
}
