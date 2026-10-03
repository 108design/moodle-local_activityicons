<?php
namespace local_activityicons;

use local_activityicons\local\repository;

/** Unit coverage for the public selection token boundary. */
final class repository_test extends \advanced_testcase {
    public function test_normalise_selection(): void {
        $this->assertSame(['inherit', null], repository::normalise_selection('inherit'));
        $this->assertSame(['default', null], repository::normalise_selection('default'));
        $this->assertSame(['automatic', null], repository::normalise_selection('auto'));
        $this->assertSame(['manual', 'video'], repository::normalise_selection('icon:video'));
    }

    public function test_rejects_unknown_icon(): void {
        $this->expectException(\invalid_parameter_exception::class);
        repository::normalise_selection('icon:not-in-the-catalog');
    }

    public function test_selection_round_trip_uses_stable_course_module_id(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $page = $this->getDataGenerator()->create_module('page', ['course' => $course->id]);
        $repository = new repository();

        $repository->set_selection((int) $course->id, (int) $page->cmid, 'icon:video');
        $this->assertSame('icon:video', $repository->get_selection((int) $page->cmid));

        $repository->set_selection((int) $course->id, (int) $page->cmid, 'default');
        $this->assertSame('default', $repository->get_selection((int) $page->cmid));

        $repository->set_selection((int) $course->id, (int) $page->cmid, 'inherit');
        $this->assertSame('inherit', $repository->get_selection((int) $page->cmid));
    }

    public function test_course_policy_and_reset_are_scoped_and_preserve_original_icons(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $other = $this->getDataGenerator()->create_course();
        $activity = $this->getDataGenerator()->create_module('h5pactivity', ['course' => $course->id]);
        $otheractivity = $this->getDataGenerator()->create_module('page', ['course' => $other->id]);
        $repository = new repository();
        set_config('autoh5p', 0, 'local_activityicons');
        $this->assertFalse($repository->course_auto_h5p_enabled($course->id));
        $repository->set_course_auto_mode($course->id, 'enabled');
        $this->assertTrue($repository->course_auto_h5p_enabled($course->id));
        $cm = get_fast_modinfo($course)->get_cm($activity->cmid);
        $resolver = new \local_activityicons\local\icon_resolver($repository);
        $this->assertSame('h5p', $resolver->effective_key($cm));
        $repository->set_selection($course->id, $activity->cmid, 'icon:quiz');
        $this->assertSame('quiz', $resolver->effective_key($cm));
        $repository->set_selection($other->id, $otheractivity->cmid, 'icon:video');
        set_config('autoh5p', 1, 'local_activityicons');
        $this->assertSame(1, $repository->reset_course($course->id));
        $this->assertSame('default', $repository->get_selection($activity->cmid));
        $this->assertNull($resolver->effective_key($cm));
        $this->assertFalse($repository->course_auto_h5p_enabled($course->id));
        $this->assertSame('icon:video', $repository->get_selection($otheractivity->cmid));
        $repository->delete_course($course->id);
        $this->assertSame('inherit', $repository->get_course_auto_mode($course->id));
    }
}
