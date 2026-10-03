# 108design Activity Icons

Prototype for assigning semantic activity icons in Moodle 4.5–5.2 without encoding control data in activity names.
Assignments use the stable `course_modules.id` and are stored independently of module content.

## What it does

- Adds an **Activity icon** choice to every standard activity settings form.
- Adds a compact icon picker directly on activity icons in course edit mode (Boost-compatible semantic markup).
- Replaces the icon through Moodle's `cm_info::set_icon_url()` before normal page rendering.
- Supplies eight monochrome SVG starter icons through Moodle's theme-aware image API.
- Accepts custom SVG/PNG/WebP uploads into a shared Moodle File API pool, usable per activity and in H5P mapping.
- Classifies the top-level H5P library for both Moodle core `mod_h5pactivity` and the optional official
  `mod_hvp` plugin as video, quiz, presentation, branching scenario, audio, image, game or generic H5P content.
- Covers AJAX-inserted standard activity links (for example Dashboard cards and timeline/recently accessed blocks) with a bounded web-service lookup.
- Includes course backup/restore and deletion cleanup.

The default after installation is conservative: existing H5P activities are unchanged. Automatic H5P classification can
be selected per activity, enabled per course or enabled as the site-wide fallback. An explicit **Use original icon** selection always
overrides that fallback.

## Compatibility and installation

The plugin targets one package for Moodle 4.5 through 5.2 and PHP 8.1 or newer. Copy the directory to
`/local/activityicons` in Moodle 4.5/5.0 or normally to `/public/local/activityicons` in the split web root used by
Moodle 5.1/5.2. Then run Moodle's normal upgrade process.

The inline picker intentionally follows Boost's `data-for="cmitem"` and `activityiconcontainer` contract. Themes that
replace this markup still retain the standard activity-form selector and server-side icon replacement.

## Architecture

`local_activityicons_map` contains one optional row per course module. The stored mode is `manual`, `automatic`, or
`default`; no row means inherit the course/site policy. `local_activityicons_course` stores course policy overrides.
`local_activityicons_pool` identifies shared custom assets by content-derived keys; files live in the system context.

Normal course rendering is server-first. JavaScript is limited to editing in place and to Moodle components that insert
activity links after the initial page was generated. The read endpoint accepts at most 100 course-module IDs and returns
only assignments the current user can see.

## Pool and manual H5P mapping

Open **Site administration → Plugins → Local plugins → Activity icons** (`/local/activityicons/pool.php`). This is the
single administration page for batch upload, pool labels, visual H5P mapping/fallback, and site-wide behaviour.
It uses Moodle's native collapsible form regions. Upload up to 50 icons per batch through Moodle's file manager;
filenames provide the initial labels. Afterwards edit custom labels alongside their previews; internal asset keys are hidden.

Upload is a separate additive action inside its region; labels, mapping and behaviour share the final Save changes action.
The pool is a responsive tile grid, with editable name fields below uploaded icons. Mapping rows contain only the type,
the visual icon/name choice and an accessible remove action. Click the plain type name to open its searchable chooser.
Long row labels truncate within their columns while full names remain available as accessible labels and titles.
The remove action dims and strikes through a row without reloading or changing its position; the same control undoes
the mark. Values remain intact through Add mapping roundtrips. Only the final Save changes commits removals.
Custom pool icons can be selected in the grid and deleted as one confirmed batch. The server rechecks all references
under the shared metadata lock: unused selections are deleted, while directly used, mapped or fallback icons remain.
Add mapping reuses and focuses any incomplete row; incomplete rows stay highlighted and a repeat click briefly pulses
the row outline (respecting reduced motion). Duplicate empty slots are collapsed server-side; partially entered data
is retained. Entirely empty rows are never stored as mappings.

Choose **Add mapping**, select a searchable H5P type, then choose an icon by its preview and display label in the picker.
Installed runnable H5P types and previously configured/default types are offered; search text never creates a new type.
Each type can appear once. Adding/removing rows
and choosing icons only edits the form; **Save changes** commits it. Versions of the same type share a mapping.
Selections persist by immutable internal identity, never by editable display label. Only exact machine names match; there are
no substring guesses, remote icon downloads or icons extracted from H5P packages. An empty mapping uses the fallback for every type.
The neutral H5P starter icon, another bundled/custom icon, or the original Moodle H5P icon can be the fallback.

Pool management requires `local/activityicons:managepool` at system level (administrators/managers by default).
Teachers with `local/activityicons:manage` can choose from the pool and manage course policies, but cannot publish
global files by default. Uploads are public decorative assets, not private course files: do not include personal data.
Static SVGs need an SVG namespace and `viewBox`; active elements and external resources are rejected/removed.
Conventional SVG 1.1 doctypes are stripped without loading a DTD; safe inline presentation styles become SVG attributes.
Other doctypes, entities and external stylesheets remain forbidden.
PNG/WebP is decoded and stored as PNG without metadata. Limits: 1 MiB input and 2048 × 2048 pixels for raster images.
Prefer monochrome icons: Moodle/theme CSS filters recolour external images; page CSS does not reach paths inside
an SVG loaded through `<img>`. The plugin deliberately does not inject uploaded SVG markup into Moodle pages.
File content and keys are immutable and deduplicated. Labels can be changed without affecting any assignment or file URL.
Re-uploading identical content reuses the existing entry and preserves its label. Each complete batch is validated before
any pool entry is added. Unused custom icons can be deleted after confirmation. Direct activity assignments, H5P mappings
and the configured fallback all prevent deletion, even when automatic classification is disabled. The server rechecks
usage under the same lock used for assignment/mapping writes and restore; bundled icons cannot be deleted.
General automation switches require `moodle/site:config`;
pool managers without that capability can edit pool labels, mapping and fallback, but cannot change those switches.

## Course policy and reset

Open **Course → More → Activity icons**. Enable automatic H5P icons for unassigned existing/new activities, disable it,
or inherit the site setting. Explicit activity choices always take precedence. **Follow course policy** in the H5P picker means
inherit; **Use original icon** bypasses automation; **Detect H5P type** enables it for this activity independently of the course.
Other activity types only offer the original icon and pool choices, avoiding redundant H5P-specific options.
The picker includes search and a footer Close button. The course settings page links to unified administration only for
users with system-level pool-management permission.

**Restore original Moodle icons** requires a separate warning/POST confirmation. It replaces every activity choice
in this course with an explicit Moodle-default choice and disables course automation. Manual assignments are lost;
back up the course first. Re-enabling course automation alone will not remove these explicit default choices.

Course backups include assignments, course policy and referenced custom icon files. Existing destination assets can
be reused by teachers. Importing new assets into the global pool requires pool-management permission and revalidates
every image; otherwise those new custom assignments are skipped. Destination site H5P mapping is never overwritten
by a course restore. Configure the type mapping separately when moving a course to another site.

## No legacy migration

This generic distribution contains no shortcode scanner, migration endpoint or migration service. Legacy shortcodes in
activity names are not interpreted at runtime. Customer-specific migration belongs in a separate plugin distribution.

## Prototype status

This is a beta release. Before production use, verify the actual platform's themes, Dashboard blocks, H5P library
set, course backup/restore and accessibility workflows. Extend the pool through the upload page without modifying code.
