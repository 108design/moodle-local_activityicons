<?php

/** Pool assets are explicitly public decorative resources; private uploads are not supported. */
function local_activityicons_pluginfile($course, $cm, context $context, string $filearea,
        array $args, bool $forcedownload, array $options = []): bool {
    global $DB;
    if ($context->contextlevel !== CONTEXT_SYSTEM || $filearea !== 'pool' || count($args) !== 2) {
        return false;
    }
    $id = (int) $args[0];
    if (!$DB->record_exists('local_activityicons_pool', ['id' => $id])) {
        return false;
    }
    $file = \local_activityicons\local\pool::file($id);
    if (!$file || $file->get_filename() !== $args[1]) {
        return false;
    }
    send_stored_file($file, DAYSECS, 0, $forcedownload, ['dontdie' => false]);
    return true;
}

/** Course policy entry in Moodle's course navigation. */
function local_activityicons_extend_navigation_course(navigation_node $navigation, stdClass $course, context $context): void {
    if (has_capability('local/activityicons:manage', $context)) {
        $navigation->add_node(navigation_node::create(
            get_string('coursemanage', 'local_activityicons'),
            new moodle_url('/local/activityicons/course.php', ['id' => $course->id]),
            navigation_node::TYPE_SETTING, null, 'local_activityicons_course', new pix_icon('i/settings', '')
        ));
    }
}

/** Add the icon choice to Moodle's standard settings form for every activity type. */
function local_activityicons_coursemodule_standard_elements($formwrapper, $mform): void {
    global $COURSE;

    if (!has_capability('local/activityicons:manage', $formwrapper->get_context())) {
        return;
    }

    $options = [
        \local_activityicons\local\repository::SELECTION_INHERIT =>
            get_string('selectioninherit', 'local_activityicons'),
        \local_activityicons\local\repository::SELECTION_DEFAULT =>
            get_string('selectiondefault', 'local_activityicons'),
        \local_activityicons\local\repository::SELECTION_AUTO =>
            get_string('selectionauto', 'local_activityicons'),
        get_string('bundledicons', 'local_activityicons') =>
            \local_activityicons\local\icon_catalog::form_options(),
    ];
    $cm = $formwrapper->get_coursemodule();
    $ish5p = \local_activityicons\local\h5p_resolver::supports_module(
        (string) ($formwrapper->get_current()->modulename ?? '')
    );
    if (!$ish5p) {
        unset($options['inherit'], $options['auto']);
    }
    $mform->addElement('select', 'local_activityicons_selection',
        get_string('activityicon', 'local_activityicons'), $options);
    $mform->addHelpButton('local_activityicons_selection', 'activityicon', 'local_activityicons');

    if ($cm && (int) $cm->course === (int) $COURSE->id) {
        $selection = (new \local_activityicons\local\repository())->get_selection((int) $cm->id);
        $mform->setDefault('local_activityicons_selection',
            !$ish5p && in_array($selection, ['inherit', 'auto'], true) ? 'default' : $selection);
    }
}

/** Persist the selection after Moodle has created or updated the course module. */
function local_activityicons_coursemodule_edit_post_actions($data, $course) {
    if (!isset($data->local_activityicons_selection, $data->coursemodule)) {
        return $data;
    }

    $context = \context_course::instance((int) $course->id);
    if (has_capability('local/activityicons:manage', $context)) {
        (new \local_activityicons\local\repository())->set_selection(
            (int) $course->id,
            (int) $data->coursemodule,
            (string) $data->local_activityicons_selection
        );
    }
    return $data;
}
