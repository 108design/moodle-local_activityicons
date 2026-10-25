/**
 * This file is part of a 108design source-available software product.
 *
 * Use and modification are permitted only under the Software License included
 * with this distribution. See LICENSE.md for the full terms.
 *
 * File documentation for amd/src/context_help_form.js.
 *
 * @package   local_activityicons
 * @copyright 2026 Andreas Giesen
 * @license   See LICENSE.md for the full terms.
 */
// Copyright (C) 2026 Andreas Giesen. See LICENSE.md.

/** Align help in our registered forms without changing fields or their values. */
export const init = () => {
    const navigation = document.querySelector('#usernavigation');
    const alignNavbarHelp = () => {
        if (!navigation) return;
        const help = navigation.querySelector('.local-activityicons-help-navbar');
        const children = [...navigation.children];
        const userMenu = children.find(node => node.matches('[data-region="usermenu"], .usermenu-container'));
        const userIndex = userMenu ? children.indexOf(userMenu) : children.length;
        // Older Moodle places its divider after the user menu, outside the help insertion area.
        const anchor = children.find((node, index) => index < userIndex &&
            node.classList.contains('divider')) || userMenu;
        if (help && anchor && (help.parentElement !== navigation || help.nextElementSibling !== anchor)) {
            navigation.insertBefore(help, anchor);
        }
    };
    // Navbar callbacks can finish in any order; keep help immediately before the user-area divider.
    alignNavbarHelp();
    if (navigation) new MutationObserver(alignNavbarHelp).observe(navigation, {childList: true});

    // Move only the linked icon from its placeholder to the native control.
    document.querySelectorAll('[data-activityicons-help-field]').forEach(holder => {
        const name = holder.dataset.activityiconsHelpField;
        const field = document.getElementById('id_' + name);
        const item = field?.closest('.fitem');
        const column = item?.querySelector('.felement, .checkbox');
        const icon = holder.querySelector('.local-activityicons-context-help-icon');
        const control = name === 'iconfiles' ? document.getElementById('id_iconfiles_fieldset') :
            field?.closest('.form-check') || field;
        if (!column || !icon || !control || !column.contains(control)) return;
        const row = document.createElement('div');
        row.className = 'local-activityicons-setting-control';
        if (name === 'iconfiles') row.classList.add('w-100');
        control.before(row);
        row.append(control, icon);
        const placeholder = holder.closest('.fitem');
        if (placeholder && placeholder !== item) placeholder.remove();
    });

    const align = () => {
        document.querySelectorAll('.mform .fitem').forEach(row => {
            const labelColumn = [...row.children].find(node => node.classList.contains('col-form-label'));
            const fieldColumn = [...row.children].find(node =>
                node.classList.contains('felement') || node.classList.contains('checkbox'));
            if (!labelColumn || !fieldColumn) return;
            const checkbox = fieldColumn.querySelector('.form-check');
            const icons = [...row.querySelectorAll(
                '.help-icon, .form-label-addon [data-toggle="popover"], .form-check > div [data-toggle="popover"]'
            )].filter(icon => icon.closest('.fitem') === row && (
                labelColumn.contains(icon) || checkbox?.contains(icon) ||
                icon.classList.contains('local-activityicons-form-help')
            ));
            if (!icons.length) return;

            // Only help nodes move. Native checkbox labels stay beside their controls.
            let addon = labelColumn.querySelector('.form-label-addon');
            icons.forEach(icon => {
                if (icon.classList.contains('local-activityicons-context-help-icon')) {
                    const iconColumn = checkbox || fieldColumn;
                    const controls = [...iconColumn.children].filter(node =>
                        !node.classList.contains('local-activityicons-context-help-icon') &&
                        !node.classList.contains('form-control-feedback'));
                    const lastControl = controls[controls.length - 1];
                    if (lastControl) {
                        if (icon.parentElement !== iconColumn ||
                                !(lastControl.compareDocumentPosition(icon) & Node.DOCUMENT_POSITION_FOLLOWING)) {
                            iconColumn.insertBefore(icon, lastControl.nextSibling);
                        }
                    } else if (icon.parentElement !== iconColumn) {
                        iconColumn.append(icon);
                    }
                    icon.classList.add('local-activityicons-form-help');
                    fieldColumn.classList.add('local-activityicons-form-help-field');
                } else if (!labelColumn.contains(icon)) {
                    if (!addon) {
                        addon = document.createElement('div');
                        addon.className = 'form-label-addon d-flex align-items-center align-self-start';
                        labelColumn.append(addon);
                    }
                    addon.append(icon);
                }
            });
            labelColumn.classList.add('d-flex', 'pe-md-0', 'pr-md-0', 'local-activityicons-form-help-label');
        });
    };
    align();
    // Settings may arrive through a native dynamic form after the page header.
    let pending = false;
    new MutationObserver(() => {
        if (pending) return;
        pending = true;
        requestAnimationFrame(() => { pending = false; align(); });
    }).observe(document.querySelector('main') || document.body, {childList: true, subtree: true});
};
