# 108design Activity Icons

Activity Icons lets course editors choose meaningful icons for Moodle activities.
Use the eight supplied icons, upload your own icons, or automatically choose icons
for H5P content types such as video, quiz, presentation, audio and games.

## Compatibility and installation

Activity Icons supports Moodle 4.5–5.2 and PHP 8.1 or newer. Install the plugin
as `local/activityicons` below Moodle's plugin webroot, then run Moodle's normal
upgrade process. Moodle 5.1 and newer normally use `public/local/activityicons`.

The inline course picker supports Boost-compatible themes. If a theme does not
show it, use the **Activity icon** field in the activity's settings instead.

## Choose an activity icon

Open an activity's settings and use **Activity icon**, then save the form.
With course editing enabled, you can also click the activity icon in the course
to open the searchable picker; a selection there saves immediately.

- **Use original icon** keeps Moodle's standard icon for that activity.
- A supplied or uploaded icon applies your individual choice.
- For H5P, **Follow course policy** uses the course's automatic-icon setting.
- For H5P, **Detect H5P type** chooses an icon from the site mapping for this
  activity, independently of the course's automatic-icon setting.

Automatic H5P icons support both Moodle's built-in H5P activity and the optional
H5P plugin (`mod_hvp`). Individual activity choices take precedence over course
and site settings.

## Set automatic icons for a course

Open **Course → More → Activity icons** and choose one of the course settings:

- **Inherit the site setting** follows the administrator's default.
- **Enable for this course** applies automatic icons to existing and new H5P
  activities without an individual choice.
- **Disable for this course** keeps the original icons for those activities.

Save the course setting. To return an individually configured H5P activity to
this policy, select **Follow course policy** for that activity.

**Restore original Moodle icons** resets every activity in the course and
disables course automation. It requires confirmation and removes individual
choices; back up the course first if you may need them again. Enabling automation
after a reset takes effect only for activities returned to **Follow course policy**.

## Manage the shared icon pool

Open **Site administration → Plugins → Local plugins → Activity Icons**
(`/local/activityicons/pool.php`). The shared pool is available to all courses.

In **Add icons**, upload up to 50 SVG, PNG or WebP files at once. Each file may
be up to 1 MiB; raster images may be up to 2048 × 2048 pixels. SVGs must have a
`viewBox` and contain static shapes. Scripts, embedded images and external
content are not supported. PNG and WebP uploads are stored as PNG.

Filenames provide the initial labels. Edit labels below the icon previews and
choose **Save changes**; renaming an icon preserves its assignments. Identical
uploads reuse the existing icon. Prefer monochrome icons for consistent results
with Moodle's theme colours. Uploaded icons are publicly accessible decorative
assets, so do not include personal or confidential information.

Save label and settings changes before uploading another batch: adding icons
is a separate action. Select unused custom icons in the grid to delete them
after confirmation. Icons used by activities, H5P mappings or the fallback
cannot be deleted; supplied icons also remain in the pool.

## Configure H5P mapping and site settings

On the same administration page, use **Add mapping**, choose an H5P content type
in the searchable chooser, then select its icon. Each type has one mapping that
applies to all its versions. To remove a mapping, mark its row for removal;
the same control undoes the mark. **Save changes** applies your edits.

**Icon for unmapped H5P types** chooses the fallback for types without a mapping.
Select a supplied or uploaded icon, or Moodle's original H5P icon. If the mapping
is empty, every automatically classified H5P activity uses this fallback.

The **Automation and display** section contains these site settings:

- **Automatically classify unassigned H5P activities** supplies the site default
  for courses that inherit it. It is disabled after installation, so existing
  courses do not change automatically.
- **Enhance dynamically loaded activity lists** also applies configured icons
  in standard Moodle lists such as Timeline and Recently accessed items. It is
  enabled by default.

## Permissions and course backups

Course editors need `local/activityicons:manage` to choose activity icons and
change course policies. Administrators and managers can manage the shared pool
and H5P mappings by default through `local/activityicons:managepool` at system
level. Changing the general site switches additionally requires `moodle/site:config`.

Course backups include icon assignments, the course policy and referenced custom
icons. Teachers can reuse icons already available at the destination. Restoring
new custom icons into its shared pool requires pool-management permission;
without it, assignments needing those new files are skipped. A course restore
preserves the destination's site-wide H5P mapping. Review that mapping separately
when transferring a course to another site.

## License

This is a 108design source-available commercial software license, not an open-source license. See [LICENSE.md](https://github.com/108design/moodle-local_activityicons/blob/main/LICENSE.md) for the full terms.
