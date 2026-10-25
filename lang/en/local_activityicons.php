<?php
/**
 * This file is part of a 108design source-available software product.
 *
 * Use and modification are permitted only under the Software License included
 * with this distribution. See LICENSE.md for the full terms.
 *
 * File documentation for lang/en/local_activityicons.php.
 *
 * @package   local_activityicons
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license   See LICENSE.md for the full terms.
 */
defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Activity Icons';
$string['activityicons:manage'] = 'Manage individual activity icons and course policies';
$string['activityicons:managepool'] = 'Manage the shared icon pool and H5P mappings';
$string['managepool'] = 'Icon pool and H5P mapping';
$string['uploadicon'] = 'Add icon';
$string['iconlabel'] = 'Label';
$string['iconfile'] = 'Icon file';
$string['poolnotice'] = 'The shared pool is available to all courses. Uploaded icons are publicly accessible decorative assets. Use SVG with a viewBox or PNG/WebP, up to 1 MB and up to 2048 × 2048 pixels for raster images. SVG must contain static shapes; scripts, embedded images and external content are not supported. Raster images are stored as PNG. Existing icons remain available when more icons are uploaded.';
$string['invalidimage'] = 'This file is not a supported safe icon. Use static SVG with a viewBox or PNG/WebP (up to 1 MB, 2048 × 2048 pixels).';
$string['invalidlabel'] = 'Enter a label of 1 to 100 characters.';
$string['pooliconsaved'] = 'The icon is available in the shared pool.';
$string['h5ptypes'] = 'H5P content types';
$string['h5ptypes_help'] = 'Select installed content types or enter additional machine names, e.g. H5P.InteractiveVideo. The mapping applies to every version of the type. Existing mappings for the selected types are replaced. Individual activity icons still take precedence.';
$string['usefallback'] = 'Remove mapping – use fallback icon';
$string['savemapping'] = 'Assign types';
$string['mappingsaved'] = 'The H5P type mapping was saved.';
$string['invalidmachine'] = 'Use an H5P machine name without a version number, e.g. H5P.InteractiveVideo.';
$string['coursemanage'] = 'Activity icons';
$string['coursemanage_desc'] = 'The course policy applies to existing and new H5P activities without an individual choice. Individual activities can still use their own icons.';
$string['courseautoh5p'] = 'Automatic H5P icons';
$string['courseautoh5p_help'] = 'Automation uses the global H5P type mapping. Individual icon choices take precedence. After a full reset, activities must first be returned to Follow course policy before automation applies to them again.';
$string['courseautoinherit'] = 'Inherit the site setting';
$string['courseautoenabled'] = 'Enable for this course';
$string['courseautodisabled'] = 'Disable for this course';
$string['coursepolicysaved'] = 'The H5P course policy was saved.';
$string['invalidcourseautomode'] = 'The selected H5P course policy is invalid.';
$string['resetall'] = 'Restore original Moodle icons';
$string['resetall_desc'] = 'Restores the original module icon for every activity in this course and disables course H5P automation.';
$string['resetconfirm'] = 'Really replace all icon assignments in this course with the original Moodle module icons? Your individual assignments will be lost. Restoring them can involve substantial manual work and requires assigning them again or restoring a course backup. H5P automation will also be disabled for this course.';
$string['resetdone'] = 'Original Moodle module icons have been restored. Course H5P automation is disabled.';
$string['activityicon'] = 'Activity icon';
$string['activityicon_help'] = 'Choose the original activity-type icon or an icon from the shared pool. H5P activities additionally allow following the course policy or detecting this activity’s H5P type independently.';
$string['autoh5p'] = 'Automatically classify unassigned H5P activities';
$string['autoh5p_desc'] = 'Review the type mapping before enabling automation.';
$string['h5pmapping'] = 'Manual H5P type mapping';
$string['h5pmapping_desc'] = 'One mapping per line, e.g. H5P.InteractiveVideo=video. Use the H5P machine name without a version number. Matching is case-insensitive. Blank lines and # comments are allowed. An empty list uses the fallback for all types. Available icon keys:';
$string['mappinginvalidline'] = 'Invalid or duplicate mapping {$a}. Choose a valid H5P type and an icon. Each type can only be mapped once.';
$string['h5pfallback'] = 'Icon for unmapped H5P types';
$string['h5pfallback_desc'] = 'This pool icon is used when the machine name is absent from the manual mapping. No icons are downloaded from the internet or loaded from the H5P package.';
$string['h5pfallbackdefault'] = 'Original Moodle H5P icon';
$string['dynamicfallback'] = 'Enhance dynamically loaded activity lists';
$string['dynamicfallback_desc'] = 'Resolve configured icons in standard AJAX-loaded Moodle blocks such as Timeline and Recently accessed items.';
$string['selectioninherit'] = 'Follow course policy';
$string['selectioninherit_desc'] = 'Uses course H5P automation; without a course override, site automation applies.';
$string['selectiondefault'] = 'Use original icon';
$string['selectiondefault_desc'] = 'Uses the original icon of this activity type.';
$string['selectionauto'] = 'Detect H5P type';
$string['selectionauto_desc'] = 'Assigns an icon from this activity’s H5P type, independently of the course policy.';
$string['bundledicons'] = 'Icon pool (bundled and custom icons)';
$string['chooseicon'] = 'Choose activity icon';
$string['closepicker'] = 'Close';
$string['editicon'] = 'Change activity icon for {$a}';
$string['searchicons'] = 'Search icons';
$string['iconsaved'] = 'Activity icon saved.';
$string['invalidselection'] = 'The selected activity icon is not valid.';
$string['unknowncoursemodule'] = 'The selected activity does not belong to this course.';
$string['privacy:metadata'] = 'Activity Icons stores course-module presentation settings but no personal data.';
$string['iconvideo'] = 'Video';
$string['iconquiz'] = 'Quiz';
$string['iconpresentation'] = 'Presentation';
$string['iconbranching'] = 'Branching scenario';
$string['iconaudio'] = 'Audio';
$string['iconimage'] = 'Image';
$string['icongame'] = 'Game';
$string['iconh5p'] = 'H5P (other types)';
$string['adminintro'] = 'Manage the shared icon pool and automatic H5P mapping here.';
$string['batchupload'] = 'Add icons';
$string['iconfiles'] = 'Icon files';
$string['batchhelp'] = 'Add your own icons for use in courses.';
$string['batchrequired'] = 'Choose between 1 and 50 icon files.';
$string['batchinvalid'] = 'The file “{$a}” is not a supported, safe icon. No icons from this batch were imported.';
$string['batchsaved'] = '{$a} distinct icons from the upload are available in the pool. You can now edit their labels.';
$string['adminsaved'] = 'Settings and labels saved.';
$string['poollabels'] = 'Icon pool and labels';
$string['labelshelp'] = 'Rename your custom icons or remove unused icons.';
$string['iconkey'] = 'Mapping key';
$string['mappinghelp'] = 'Choose the matching icon for each H5P type.';
$string['sitebehaviour'] = 'Automation and display';
$string['dynamichelp'] = 'Enhance dynamically loaded standard activity lists, such as Timeline and Recently accessed items.';
$string['adminlink'] = 'Manage the icon pool, H5P mapping and site automation';
$string['mappingrow'] = 'Mapping {no}';
$string['addmapping'] = 'Add mapping';
$string['removemapping'] = 'Remove mapping';
$string['undoremovemapping'] = 'Undo removal';
$string['chooseh5ptype'] = 'Choose H5P content type';
$string['iconinuse_short'] = 'In use – cannot delete';
$string['iconinuse'] = 'This icon is used by an activity, H5P type or fallback and cannot be deleted.';
$string['deleteicon'] = 'Delete custom icon';
$string['deleteiconconfirm'] = 'Delete the custom icon “{$a}” from the shared pool? It is currently unused. The file and pool entry will be removed; this cannot be undone here.';
$string['icondeleted'] = 'The custom icon was deleted from the shared pool.';
$string['deleteiconsselected'] = 'Delete selected icons';
$string['selecticondelete'] = 'Select “{$a}” for deletion';
$string['iconinuse_batch'] = 'In use – cannot be selected';
$string['deleteicons'] = 'Delete custom icons';
$string['deleteiconsconfirm'] = '{$a->selected} custom icons are selected. Unused icons will be permanently deleted. {$a->locked} icons currently in use or assigned will be kept. Usage is checked again before deletion.';
$string['iconsdeletedbatch'] = '{$a->deleted} icons were deleted. {$a->kept} icons in use or assigned were kept.';
$string['selecticonsfirst'] = 'Select at least one custom icon.';
$string['invalidiconselection'] = 'The icon selection is invalid.';

$string['settingshelp'] = 'Activity Icons Help';

$string['contexthelptopics'] = 'Help Topics';

$string['settingshelpmore'] = 'More in the help panel';

$string['settingshelp_overview'] = 'Manage Activity Icons';

$string['overview_help'] = 'Uploads have their own action. Save edited labels and settings with Save changes before starting an upload.';

$string['settingshelp_overview_article'] = '<p>Use <strong>Add icons</strong> to extend the shared pool. Edit names in <strong>Icon pool and labels</strong>, then choose icons for H5P types in <strong>Manual H5P type mapping</strong>.</p><p>Uploads have their own action. Use <strong>Save changes</strong> for labels, mappings and settings before starting an upload. Expanding a section or opening help does not save edits.</p><p>Course administrators can manage their course policy separately. An individual activity choice takes precedence over automatic choices.</p>';

$string['settingshelp_pool'] = 'Add and Manage Icons';

$string['pool_help'] = 'Pool icons are shared across courses and publicly accessible. Icons currently in use are protected from deletion.';

$string['settingshelp_pool_article'] = '<p>In <strong>Add icons</strong>, choose up to 50 SVG, PNG or WebP files, at most 1 MB each. Raster images may be up to 2048 × 2048 pixels. SVG files must be static and include a viewBox.</p><p>Use only decorative artwork suitable for public access. Uploaded icons are available across courses; avoid private information or artwork you may not redistribute.</p><p>In <strong>Icon pool and labels</strong>, rename custom icons and save your changes. Renaming does not change existing assignments. Select unused custom icons and choose <strong>Delete selected icons</strong> to review deletion. Icons used by activities, type mappings or the fallback are protected; bundled icons cannot be removed here.</p>';

$string['settingshelp_mapping'] = 'Map H5P Types';

$string['mapping_help'] = 'Choose one icon per H5P type. Marked removals take effect only when you save; the undo control restores a marked row before saving.';

$string['settingshelp_mapping_article'] = '<p>In <strong>Manual H5P type mapping</strong>, use <strong>Add mapping</strong>, choose an H5P content type and select its icon. Each type has one mapping, shared across its versions.</p><p>Use × to mark a mapping for removal or ↶ to undo that choice. <strong>Save changes</strong> applies the edits. Choose an <strong>Icon for unmapped H5P types</strong> for types without a mapping, or retain the original Moodle H5P icon.</p><p>These mappings are used when H5P type detection applies. An explicit choice for an individual activity still takes precedence.</p>';

$string['settingshelp_behaviour'] = 'Automation and Display';

$string['behaviour_help'] = 'Site automation affects H5P activities that have no individual choice and follow the site policy. Courses can override it.';

$string['settingshelp_behaviour_article'] = '<p><strong>Automatically classify unassigned H5P activities</strong> supplies a site default. It starts disabled so installing the plugin does not silently change existing courses. Review your type mappings and fallback before enabling it.</p><p>A course can enable or disable automatic H5P icons independently. For an activity, <strong>Use original icon</strong> keeps the native icon, a selected pool icon fixes that choice, and <strong>Detect H5P type</strong> requests type detection independently of the course policy.</p><p><strong>Enhance dynamically loaded activity lists</strong> also applies configured icons in standard lists such as Timeline and Recently accessed items. It does not change the activity content.</p><p>Site-wide switches are available only to users allowed to change site configuration.</p>';

$string['overview'] = $string['settingshelp_overview'];

$string['pool'] = $string['settingshelp_pool'];

$string['mapping'] = $string['settingshelp_mapping'];

$string['behaviour'] = $string['settingshelp_behaviour'];

$string['cachedef_h5ptypes'] = 'H5P content types for activity icons';

$string['iconfiles_help'] = 'Upload decorative icons suitable for public access. Removing a file from this draft does not delete an existing pool icon.';
$string['h5pfallback_help'] = 'This icon is used for H5P types without a mapping. An individual activity choice takes precedence.';
$string['autoh5p_help'] = 'The site default applies to courses that inherit it. Courses can enable or disable automatic H5P icons separately.';
$string['dynamicfallback_help'] = 'Apply configured icons in standard activity lists that load dynamically, such as Timeline and Recently accessed items.';
