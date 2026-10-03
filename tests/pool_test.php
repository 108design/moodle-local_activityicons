<?php
namespace local_activityicons;

use local_activityicons\local\pool;
use local_activityicons\local\image_validator;
use local_activityicons\local\h5p_resolver;
use local_activityicons\local\icon_catalog;
use local_activityicons\local\repository;

/** Custom assets are immutable, validated and usable through the normal assignment API. */
final class pool_test extends \advanced_testcase {
    private const SVG = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">' .
        '<path fill="none" stroke="currentColor" d="M2 2L22 22"/></svg>';

    public function test_upload_mapping_and_assignment(): void {
        $this->resetAfterTest();
        $record = pool::store('Own line icon', 'line.svg', self::SVG);
        $this->assertEquals($record->id, pool::store('Duplicate', 'again.svg', self::SVG)->id);
        $this->assertContains($record->iconkey, icon_catalog::keys());
        $this->assertSame('image/svg+xml', pool::file($record->id)->get_mimetype());
        $this->assertEquals(0, pool::file($record->id)->get_userid());
        h5p_resolver::assign_types(['H5P.InteractiveVideo'], $record->iconkey);
        $this->assertSame($record->iconkey, h5p_resolver::configured_mapping()['interactivevideo']);
        $course = $this->getDataGenerator()->create_course();
        $page = $this->getDataGenerator()->create_module('page', ['course' => $course->id]);
        $repository = new repository();
        $repository->set_selection($course->id, $page->cmid, 'icon:' . $record->iconkey);
        $this->assertSame('icon:' . $record->iconkey, $repository->get_selection($page->cmid));
        $this->assertCount(1, pool::backup_records($course->id));
        h5p_resolver::assign_types(['H5P.InteractiveVideo'], '');
        $this->assertArrayNotHasKey('interactivevideo', h5p_resolver::configured_mapping());
    }

    public function test_svg_strips_active_attributes_and_rejects_active_elements(): void {
        $result = image_validator::sanitise('safe.svg', str_replace('<path ',
            '<path onload="alert(1)" style="fill:url(https://example.org/x)" ', self::SVG));
        $this->assertStringNotContainsString('onload', $result['content']);
        $this->assertStringNotContainsString('style=', $result['content']);
        $this->assertStringContainsString('currentColor', $result['content']);
        $this->assertSame($result, image_validator::sanitise('safe.svg', $result['content']));
        $this->expectException(\invalid_parameter_exception::class);
        image_validator::sanitise('bad.svg', str_replace('</svg>', '<script>alert(1)</script></svg>', self::SVG));
    }

    public function test_moodle_upload_form_validates_its_draft_without_recursion(): void {
        global $USER;
        $this->resetAfterTest();
        $this->setAdminUser();
        $draftid = file_get_unused_draft_itemid();
        get_file_storage()->create_file_from_string([
            'contextid' => \context_user::instance($USER->id)->id, 'component' => 'user',
            'filearea' => 'draft', 'itemid' => $draftid, 'filepath' => '/', 'filename' => 'line.svg',
        ], self::SVG);
        get_file_storage()->create_file_from_string([
            'contextid' => \context_user::instance($USER->id)->id, 'component' => 'user',
            'filearea' => 'draft', 'itemid' => $draftid, 'filepath' => '/', 'filename' => 'second.svg',
        ], str_replace('M2 2L22 22', 'M2 22L22 2', self::SVG));
        \local_activityicons\form\pool_icon::mock_submit(['iconfiles' => $draftid]);
        $form = new \local_activityicons\form\pool_icon();
        $this->assertNotNull($form->get_data());
        $this->assertSame(2, \local_activityicons\local\administration::import_draft($draftid));
        $this->assertCount(2, pool::records());
        $this->assertSame(['line', 'second'], array_values(array_map(static fn($icon) => $icon->label, pool::records())));
        $records = pool::records();
        $record = reset($records);
        $key = $record->iconkey;
        $hash = pool::file($record->id)->get_contenthash();
        $data = \local_activityicons\local\administration::defaults();
        $data->labels[$record->id] = 'Renamed icon';
        $data->h5pmapping = 'H5P.InteractiveVideo=' . $key;
        \local_activityicons\local\administration::save($data);
        $this->assertSame('Renamed icon', pool::records()[$key]->label);
        $this->assertSame($hash, pool::file($record->id)->get_contenthash());
        $this->assertSame($key, h5p_resolver::configured_mapping()['interactivevideo']);
        \local_activityicons\local\administration::import_draft($draftid);
        $this->assertCount(2, pool::records());
        $this->assertSame('Renamed icon', pool::records()[$key]->label);
    }

    public function test_invalid_batch_is_rejected_before_any_pool_write(): void {
        global $USER;
        $this->resetAfterTest();
        $this->setAdminUser();
        $draftid = file_get_unused_draft_itemid();
        foreach (['good.svg' => self::SVG, 'invalid.svg' => '<svg><script/></svg>'] as $name => $bytes) {
            get_file_storage()->create_file_from_string([
                'contextid' => \context_user::instance($USER->id)->id, 'component' => 'user',
                'filearea' => 'draft', 'itemid' => $draftid, 'filepath' => '/', 'filename' => $name,
            ], $bytes);
        }
        try {
            \local_activityicons\local\administration::import_draft($draftid);
            $this->fail('Unsafe batch accepted');
        } catch (\invalid_parameter_exception $exception) {
            $this->assertStringContainsString('invalid.svg', $exception->debuginfo);
        }
        $this->assertEmpty(pool::records());
    }

    public function test_teacher_cannot_change_shared_settings(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($user->id, $course->id, 'editingteacher');
        $this->setUser($user);
        $this->assertFalse(has_capability('local/activityicons:managepool', \context_system::instance()));
        $this->expectException(\required_capability_exception::class);
        \local_activityicons\local\administration::save(\local_activityicons\local\administration::defaults());
    }

    public function test_administration_form_submits_editable_labels(): void {
        global $PAGE;
        $this->resetAfterTest();
        $this->setAdminUser();
        $PAGE->set_context(\context_system::instance());
        $record = pool::store('Original label', 'line.svg', self::SVG);
        $data = \local_activityicons\local\administration::defaults();
        $data->labels[$record->id] = 'Updated label';
        \local_activityicons\form\administration::mock_submit((array) $data);
        $form = new \local_activityicons\form\administration();
        $submitted = $form->get_data();
        $this->assertNotNull($submitted);
        $this->assertSame('Updated label', $submitted->labels[$record->id]);
        \local_activityicons\local\administration::save($submitted);
        $this->assertSame('Updated label', pool::records()[$record->iconkey]->label);
    }

    public function test_compact_form_keeps_native_controls_and_accessible_actions(): void {
        global $PAGE;
        $this->resetAfterTest();
        $this->setAdminUser();
        $PAGE->set_context(\context_system::instance());
        $record = pool::store('Editable label', 'line.svg', self::SVG);
        $locked = pool::store('Assigned label', 'assigned.svg',
            str_replace('M2 2L22 22', 'M1 1L10 10', self::SVG));
        set_config('h5pfallback', $locked->iconkey, 'local_activityicons');
        $form = new \local_activityicons\form\administration();
        $form->set_data(\local_activityicons\local\administration::defaults());
        $doc = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        try {
            $doc->loadHTML('<?xml encoding="UTF-8">' . $form->render());
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        $xpath = new \DOMXPath($doc);
        $cards = '//div[contains(concat(" ", @class, " "), " local-activityicons-pool-card ")]';
        $this->assertSame(count(icon_catalog::keys()), $xpath->query($cards)->length);
        $this->assertSame(1, $xpath->query($cards . '//input[@name="labels[' . $record->id . ']"]')->length);
        $selection = $xpath->query($cards . '//input[@name="deleteicons[' . $record->id . ']"]')->item(0);
        $this->assertNotNull($selection);
        $this->assertSame(get_string('selecticondelete', 'local_activityicons', $record->label),
            $selection->getAttribute('aria-label'));
        $lockedselection = $xpath->query($cards . '//input[@name="deleteicons[' . $locked->id . ']"]')->item(0);
        $this->assertNotNull($lockedselection);
        $this->assertSame('disabled', $lockedselection->getAttribute('disabled'));
        $this->assertSame('true', $lockedselection->getAttribute('aria-hidden'));
        $lockedtrigger = $xpath->query($cards . '//span[contains(concat(" ", @class, " "), ' .
            '" local-activityicons-pool-delete-tooltip ")]')->item(0);
        $this->assertNotNull($lockedtrigger);
        $this->assertSame('tooltip', $lockedtrigger->getAttribute('data-toggle'));
        $this->assertSame(get_string('iconinuse_batch', 'local_activityicons'),
            $lockedtrigger->getAttribute('title'));
        $this->assertStringContainsString(get_string('iconinuse_batch', 'local_activityicons'),
            $lockedtrigger->getAttribute('aria-label'));
        $this->assertSame(0, $xpath->query($cards . '//*[normalize-space(text())="' .
            get_string('iconinuse_batch', 'local_activityicons') . '"]')->length);
        $batchdelete = $xpath->query('//input[@name="deleteiconsubmit"]')->item(0);
        $this->assertNotNull($batchdelete);
        $this->assertStringEndsWith('/local/activityicons/delete.php', $batchdelete->getAttribute('formaction'));
        $this->assertStringNotContainsString('outline-danger', $batchdelete->getAttribute('class'));
        $batchrow = $xpath->query('//*[@id="fitem_id_deleteiconsubmit"]')->item(0);
        $this->assertNotNull($batchrow);
        $this->assertStringNotContainsString('outline-danger', $batchrow->getAttribute('class'));
        $this->assertSame(1, $xpath->query('//select[@name="machine[0]"]')->length);
        $this->assertSame(0, $xpath->query('//table')->length);
        $remove = $xpath->query('//input[@name="removemapping[0]"]')->item(0);
        $this->assertSame('×', $remove->getAttribute('value'));
        $this->assertSame(get_string('removemapping', 'local_activityicons'), $remove->getAttribute('aria-label'));
        $this->assertSame(0, $xpath->query('//input[@name="submitbutton"]/ancestor::fieldset')->length);
    }

    public function test_compact_form_reuses_empty_mapping_slot_on_repeated_add(): void {
        global $PAGE;
        $this->resetAfterTest();
        $this->setAdminUser();
        $PAGE->set_context(\context_system::instance());
        set_config('h5pmapping', 'H5P.Video=video', 'local_activityicons');
        $data = \local_activityicons\local\administration::defaults();
        $data->machine = ['H5P.video', '', ''];
        $data->mappingicon = ['video', '', ''];
        $data->mappingcount = 3;
        $data->addmapping = 'Add';
        \local_activityicons\form\administration::mock_submit((array) $data);
        $form = new \local_activityicons\form\administration();
        $this->assertTrue($form->is_submitted());
        $html = $form->render();
        $this->assertStringContainsString('name="machine[1]"', $html);
        $this->assertStringNotContainsString('name="machine[2]"', $html);
        $this->assertStringNotContainsString('name="machine[3]"', $html);
        $this->assertStringContainsString('data-focus-row="1"', $html);
        $this->assertSame('H5P.video=video', \local_activityicons\local\administration::mapping_input($data));
        $this->assertSame('H5P.Video=video', get_config('local_activityicons', 'h5pmapping'));
    }

    public function test_mapping_removal_is_reversible_until_final_save(): void {
        global $PAGE;
        $this->resetAfterTest();
        $this->setAdminUser();
        $PAGE->set_context(\context_system::instance());
        set_config('h5pmapping', "H5P.Video=video\nH5P.Audio=audio", 'local_activityicons');
        $before = get_config('local_activityicons', 'h5pmapping');
        $data = \local_activityicons\local\administration::defaults();
        $data->mappingcount = 2;
        $data->mappingdeleted = [1, 0];
        $data->addmapping = 'Add';
        \local_activityicons\form\administration::mock_submit((array) $data);
        $form = new \local_activityicons\form\administration();
        $submitted = $form->get_submitted_data();
        $this->assertSame('H5P.video', $submitted->machine[0]);
        $this->assertSame('video', $submitted->mappingicon[0]);
        $this->assertEquals(1, $submitted->mappingdeleted[0]);
        $this->assertStringContainsString('name="machine[0]"', $form->render());
        $this->assertStringContainsString('name="machine[2]"', $form->render());
        $this->assertSame('H5P.audio=audio', \local_activityicons\local\administration::mapping_input($submitted));
        $this->assertSame($before, get_config('local_activityicons', 'h5pmapping'));

        // Native non-JavaScript undo also keeps the row values and does not save.
        unset($data->addmapping);
        $data->removemapping = [0 => 'Undo'];
        \local_activityicons\form\administration::mock_submit((array) $data);
        $form = new \local_activityicons\form\administration();
        $undone = $form->get_submitted_data();
        $this->assertEquals(0, $undone->mappingdeleted[0]);
        $this->assertSame("H5P.video=video\nH5P.audio=audio",
            \local_activityicons\local\administration::mapping_input($undone));
        $this->assertSame($before, get_config('local_activityicons', 'h5pmapping'));

        // Final Save alone commits the removal; the unmarked mapping is unchanged.
        unset($data->removemapping);
        $data->submitbutton = 'Save changes';
        \local_activityicons\form\administration::mock_submit((array) $data);
        $form = new \local_activityicons\form\administration();
        $final = $form->get_data();
        $this->assertNotNull($final);
        \local_activityicons\local\administration::save($final);
        $this->assertSame('H5P.audio=audio', get_config('local_activityicons', 'h5pmapping'));
    }

    public function test_pool_manager_cannot_override_site_config_switches(): void {
        $this->resetAfterTest();
        $manager = $this->getDataGenerator()->create_user();
        $role = $this->getDataGenerator()->create_role();
        $context = \context_system::instance();
        assign_capability('local/activityicons:managepool', CAP_ALLOW, $role, $context->id);
        role_assign($role, $manager->id, $context->id);
        $this->setUser($manager);
        $this->assertFalse(has_capability('moodle/site:config', $context));
        set_config('autoh5p', 0, 'local_activityicons');
        set_config('dynamicfallback', 1, 'local_activityicons');
        $data = \local_activityicons\local\administration::defaults();
        $data->autoh5p = 1;
        $data->dynamicfallback = 0;
        $data->h5pfallback = 'video';
        \local_activityicons\local\administration::save($data);
        $this->assertEquals(0, get_config('local_activityicons', 'autoh5p'));
        $this->assertEquals(1, get_config('local_activityicons', 'dynamicfallback'));
        $this->assertSame('video', get_config('local_activityicons', 'h5pfallback'));
    }

    public function test_svg_rejects_external_entities(): void {
        $this->expectException(\invalid_parameter_exception::class);
        image_validator::sanitise('bad.svg', '<!DOCTYPE svg [<!ENTITY x SYSTEM "file:///etc/passwd">]>' . self::SVG);
    }

    public function test_svg_export_doctype_and_presentation_styles_are_safe(): void {
        $svg = '<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" ' .
            '"http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">' .
            str_replace('<path ', '<path style="fill:#4fcfab;fill-rule:nonzero;stroke-miterlimit:2" ', self::SVG);
        $result = image_validator::sanitise('logo-n.svg', $svg);
        $this->assertStringContainsString('fill="#4fcfab"', $result['content']);
        $this->assertStringContainsString('fill-rule="nonzero"', $result['content']);
        $this->assertStringNotContainsString('DOCTYPE', $result['content']);
        $this->assertStringNotContainsString('style=', $result['content']);
        $this->assertSame($result, image_validator::sanitise('logo-n.svg', $result['content']));
    }

    public function test_visual_mapping_rows_keep_identity_when_label_changes(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $record = pool::store('logo-n', 'logo-n.svg', self::SVG);
        $url = pool::url($record)->out(false);
        $data = \local_activityicons\local\administration::defaults();
        $data->machine = ['H5P.InteractiveVideo'];
        $data->mappingicon = [$record->iconkey];
        $data->labels[$record->id] = 'My new name';
        \local_activityicons\local\administration::save($data);
        $this->assertSame($record->iconkey, h5p_resolver::configured_mapping()['interactivevideo']);
        $this->assertSame('My new name', pool::records()[$record->iconkey]->label);
        $this->assertSame($url, pool::url(pool::records()[$record->iconkey])->out(false));
        $this->assertArrayHasKey($record->iconkey, pool::used_keys());
        $data = \local_activityicons\local\administration::defaults();
        $this->assertSame([$record->iconkey], $data->mappingicon);
        $this->assertSame(['H5P.interactivevideo'], $data->machine);
        $this->expectException(\moodle_exception::class);
        pool::delete_unused($record->id);
    }

    public function test_direct_use_without_h5p_mapping_blocks_deletion(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $record = pool::store('Direct', 'direct.svg', self::SVG);
        $course = $this->getDataGenerator()->create_course();
        $page = $this->getDataGenerator()->create_module('page', ['course' => $course->id]);
        (new repository())->set_selection($course->id, $page->cmid, 'icon:' . $record->iconkey);
        $this->assertNotContains($record->iconkey, h5p_resolver::configured_mapping());
        $this->expectException(\moodle_exception::class);
        pool::delete_unused($record->id);
    }

    public function test_fallback_blocks_deletion_even_when_automation_is_disabled(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $record = pool::store('Fallback', 'fallback.svg', self::SVG);
        set_config('autoh5p', 0, 'local_activityicons');
        set_config('h5pfallback', $record->iconkey, 'local_activityicons');
        $this->expectException(\moodle_exception::class);
        pool::delete_unused($record->id);
    }

    public function test_deletion_refreshes_config_cached_before_lock(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $record = pool::store('Cached reference', 'cached.svg', self::SVG);
        set_config('h5pmapping', '', 'local_activityicons');
        $this->assertSame('', get_config('local_activityicons', 'h5pmapping'));
        // Model another writer committing while this request already cached the previous configuration.
        $DB->set_field('config_plugins', 'value', 'H5P.Video=' . $record->iconkey,
            ['plugin' => 'local_activityicons', 'name' => 'h5pmapping']);
        $this->expectExceptionMessage(get_string('iconinuse', 'local_activityicons'));
        pool::delete_unused($record->id);
    }

    public function test_unused_custom_icon_and_only_its_file_are_deleted(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $record = pool::store('Delete', 'delete.svg', self::SVG);
        $other = pool::store('Keep', 'keep.svg', str_replace('M2 2L22 22', 'M1 1L10 10', self::SVG));
        pool::delete_unused($record->id);
        $this->assertArrayNotHasKey($record->iconkey, pool::records());
        $this->assertNull(pool::file($record->id));
        $this->assertNotNull(pool::file($other->id));
        $this->assertArrayHasKey($other->iconkey, pool::records());
        $this->assertContains('video', icon_catalog::keys());
    }

    public function test_batch_deletion_removes_unused_and_retains_used_icons(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $unused = pool::store('Delete', 'delete.svg', self::SVG);
        $used = pool::store('Keep', 'keep.svg', str_replace('M2 2L22 22', 'M1 1L10 10', self::SVG));
        set_config('h5pfallback', $used->iconkey, 'local_activityicons');

        $result = pool::delete_unused_batch([$unused->id, $used->id]);

        $this->assertSame(['deleted' => 1, 'kept' => 1], $result);
        $this->assertNull(pool::file($unused->id));
        $this->assertArrayNotHasKey($unused->iconkey, pool::records());
        $this->assertNotNull(pool::file($used->id));
        $this->assertArrayHasKey($used->iconkey, pool::records());
    }

    public function test_batch_delete_confirmation_keeps_selected_ids(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $form = new \local_activityicons\form\delete_icons(null, ['ids' => '12,34']);
        $this->assertMatchesRegularExpression('/name="ids"[^>]*value="12,34"/', $form->render());
    }

    public function test_teacher_cannot_delete_unused_shared_icon(): void {
        $this->resetAfterTest();
        $record = pool::store('Protected', 'protected.svg', self::SVG);
        $this->setUser($this->getDataGenerator()->create_user());
        $this->expectException(\required_capability_exception::class);
        pool::delete_unused($record->id);
    }

    public function test_svg_rejects_foreign_content(): void {
        $this->expectException(\invalid_parameter_exception::class);
        image_validator::sanitise('bad.svg', str_replace('</svg>', '<foreignObject/></svg>', self::SVG));
    }

    public function test_raster_is_reencoded_and_wrong_extension_is_rejected(): void {
        $image = imagecreatetruecolor(16, 16);
        ob_start();
        imagepng($image);
        $bytes = ob_get_clean();
        imagedestroy($image);
        $clean = image_validator::sanitise('image.png', $bytes . 'PRIVATE-METADATA');
        $this->assertStringNotContainsString('PRIVATE-METADATA', $clean['content']);
        $this->assertSame('image/png', $clean['mimetype']);
        $this->expectException(\invalid_parameter_exception::class);
        image_validator::sanitise('image.webp', $bytes);
    }

    public function test_oversize_is_rejected(): void {
        $this->expectException(\invalid_parameter_exception::class);
        image_validator::sanitise('large.svg', str_repeat('x', image_validator::MAX_BYTES + 1));
    }
}
