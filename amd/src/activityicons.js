// This file is part of Moodle - https://moodle.org/
/**
 * This file is part of a 108design source-available software product.
 *
 * Use and modification are permitted only under the Software License included
 * with this distribution. See LICENSE.md for the full terms.
 *
 * File documentation for amd/src/activityicons.js.
 *
 * @package   local_activityicons
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license   See LICENSE.md for the full terms.
 */
import Ajax from 'core/ajax';
import Modal from 'core/modal';
import Notification from 'core/notification';
import {add as addToast} from 'core/toast';
import Config from 'core/config';

const courseItemSelector = '[data-for="cmitem"]';

/** Return a module id only for a standard activity view in this Moodle installation. */
const activityId = link => {
    if (!link?.matches('a[href]')) {
        return null;
    }
    try {
        const base = new URL(Config.wwwroot);
        const url = new URL(link.href, window.location.href);
        const modulePath = base.pathname.replace(/\/$/, '') + '/mod/';
        if (url.origin !== base.origin || !url.pathname.startsWith(modulePath)
                || !/^[^/]+\/view\.php$/.test(url.pathname.slice(modulePath.length))) {
            return null;
        }
        const cmid = url.searchParams.get('id');
        return cmid && /^\d+$/.test(cmid) ? cmid : null;
    } catch (error) {
        return null;
    }
};

let instance = null;

const element = (tag, classes = '', text = '') => {
    const node = document.createElement(tag);
    node.className = classes;
    node.textContent = text;
    return node;
};

const addFoundRoot = (found, cmid, root) => {
    if (!found.has(cmid)) {
        found.set(cmid, new Set());
    }
    found.get(cmid).add(root);
};

class ActivityIcons {
    constructor(config) {
        this.config = config;
        this.descriptors = new Map(Object.entries(config.descriptors || {}));
        this.selections = new Map(Object.entries(config.selections || {}));
        this.requested = new Set();
        this.dynamicFallbackAvailable = Boolean(config.dynamicfallback);
        this.scanTimer = null;
        this.observer = new MutationObserver(() => this.scheduleScan());
        this.observer.observe(document.body, {childList: true, subtree: true});
        this.scan();
    }

    scheduleScan() {
        window.clearTimeout(this.scanTimer);
        this.scanTimer = window.setTimeout(() => this.scan(), 80);
    }

    scan() {
        const found = new Map();
        document.querySelectorAll('[data-for="cmitem"][data-id]').forEach(node => {
            addFoundRoot(found, String(node.dataset.id), node);
        });
        document.querySelectorAll('a[href*="/mod/"][href*="id="]').forEach(link => {
            const cmid = activityId(link);
            if (cmid) {
                addFoundRoot(found, cmid, link);
            }
        });

        found.forEach((roots, cmid) => {
            const descriptor = this.descriptors.get(cmid);
            roots.forEach(root => {
                if (descriptor) {
                    this.apply(root, descriptor);
                }
                if (this.config.editing && this.config.canmanage && root.matches('[data-for="cmitem"]')) {
                    this.addEditButton(root, cmid);
                }
            });
        });

        if (this.dynamicFallbackAvailable) {
            const unresolved = [...found.keys()].filter(cmid => {
                const roots = found.get(cmid);
                const alreadyRenderedByCourse = this.config.courseid > 0 &&
                    [...roots].some(root => root.matches('[data-for="cmitem"]'));
                const hasIcon = [...roots].some(root => this.imageIn(root));
                return hasIcon && !alreadyRenderedByCourse && !this.descriptors.has(cmid) && !this.requested.has(cmid);
            });
            if (unresolved.length) {
                this.fetch(unresolved.slice(0, 100));
            }
        }
    }

    imageIn(root) {
        const item = root.matches(courseItemSelector) ? root : root.closest(courseItemSelector);
        const cmid = root === item ? item.dataset.id : activityId(root);
        if (!cmid) {
            return null;
        }

        // A shared course row is insufficient: an image can link to a different activity.
        const belongs = image => {
            const link = image.closest('a[href]');
            return image.closest(courseItemSelector) === item && (!link || activityId(link) === cmid);
        };
        if (item && (root === item || item.dataset.id === cmid)) {
            return [...item.querySelectorAll(
                'img.activityicon, .activityiconcontainer img, img[src*="/mod/"][src*="icon"]'
            )].find(belongs) || null;
        }

        const nested = [...root.querySelectorAll(
            'img.activityicon, img.moduleIcon, .activityiconcontainer img, img.icon, ' +
            'img[src*="/mod/"][src*="icon"]'
        )].find(belongs);
        if (nested || item || !root.matches('a')) {
            return nested || null;
        }

        // Retain third-party adjacent icons, but never borrow an image linked to another module.
        const previous = root.previousElementSibling;
        const selector = 'img.activityicon, img.moduleIcon, img.icon';
        const adjacent = previous?.matches(selector) ? previous
            : previous?.childElementCount === 1 ? previous.querySelector(selector) : null;
        return adjacent && belongs(adjacent) ? adjacent : null;
    }

    apply(root, descriptor) {
        const image = this.imageIn(root);
        if (!image || !descriptor.url) {
            return;
        }
        image.src = descriptor.url;
        const container = image.closest('.activityiconcontainer');
        if (container) {
            container.classList.toggle('isbranded', Boolean(descriptor.branded));
            container.classList.toggle('local-activityicons-custom', descriptor.kind === 'custom');
        }
    }

    addEditButton(item, cmid) {
        const container = [...item.querySelectorAll('.activityiconcontainer')]
            .find(node => node.closest(courseItemSelector) === item);
        if (!container || [...container.querySelectorAll('[data-local-activityicons-edit]')]
                .some(button => button.closest(courseItemSelector) === item)) {
            return;
        }
        const button = element('button', 'local-activityicons-edit', this.config.strings.choose);
        button.type = 'button';
        button.dataset.localActivityiconsEdit = '1';
        button.setAttribute('aria-label', this.config.strings.choose);
        button.addEventListener('click', event => {
            event.preventDefault();
            event.stopPropagation();
            this.openPicker(Number(cmid), button);
        });
        container.append(button);
    }

    pickerBody(cmid) {
        const body = element('div');
        const label = element('label', 'w-100', this.config.strings.search);
        const search = element('input', 'form-control mb-3');
        search.type = 'search';
        search.dataset.iconSearch = '1';
        label.append(search);
        body.append(label);
        const root = element('div', 'local-activityicons-picker');
        const ish5p = (this.config.h5pcmids || []).includes(cmid);
        let current = this.selections.get(String(cmid)) || 'inherit';
        if (!ish5p && ['inherit', 'auto'].includes(current)) {
            current = 'default';
        }
        const choices = [
            {
                selection: 'inherit',
                label: this.config.strings.inherit,
                description: this.config.strings.inheritDescription,
            },
            {
                selection: 'default',
                label: this.config.strings.default,
                description: this.config.strings.defaultDescription,
            },
            {
                selection: 'auto',
                label: this.config.strings.auto,
                description: this.config.strings.autoDescription,
            },
            ...(this.config.catalog || []),
        ];
        choices.filter(choice => ish5p || !['inherit', 'auto'].includes(choice.selection)).forEach(choice => {
            const button = element('button', 'btn local-activityicons-choice');
            button.type = 'button';
            button.dataset.selection = choice.selection;
            button.dataset.search = `${choice.label} ${choice.search || ''}`.toLocaleLowerCase();
            button.setAttribute('aria-pressed', choice.selection === current ? 'true' : 'false');
            if (choice.url) {
                const image = element('img', 'activityicon');
                image.src = choice.url;
                image.alt = '';
                button.append(image);
            }
            button.append(element('span', 'local-activityicons-choice-label', choice.label));
            if (choice.description) {
                button.append(element(
                    'span',
                    'local-activityicons-choice-description',
                    choice.description
                ));
            }
            root.append(button);
        });
        body.append(root);
        return body;
    }

    async openPicker(cmid, returnElement) {
        const body = this.pickerBody(cmid);
        const close = element('button', 'btn btn-primary', this.config.strings.close);
        close.type = 'button';
        close.dataset.localActivityiconsClose = '1';
        const modal = await Modal.create({
            title: this.config.strings.choose,
            body: body.outerHTML,
            footer: close.outerHTML,
            removeOnClose: true,
            returnElement,
        });
        const modalRoot = modal.getRoot()[0];
        modalRoot.querySelector('[data-local-activityicons-close]').addEventListener('click', () => modal.hide());
        const picker = modalRoot.querySelector('.local-activityicons-picker');
        modalRoot.querySelector('[data-icon-search]').addEventListener('input', event => {
            const query = event.target.value.trim().toLocaleLowerCase();
            picker.querySelectorAll('[data-selection]').forEach(button => {
                button.hidden = button.dataset.selection.startsWith('icon:') && !button.dataset.search.includes(query);
            });
        });
        picker.addEventListener('click', async event => {
            const button = event.target.closest('[data-selection]');
            if (!button || button.disabled) {
                return;
            }
            picker.querySelectorAll('button').forEach(node => {
                node.disabled = true;
            });
            try {
                const response = await Ajax.call([{
                    methodname: 'local_activityicons_set_icon',
                    args: {courseid: this.config.courseid, cmid, selection: button.dataset.selection},
                }])[0];
                const descriptor = JSON.parse(response.descriptorjson);
                this.descriptors.set(String(cmid), descriptor);
                this.selections.set(String(cmid), descriptor.selection);
                document.querySelectorAll(`[data-for="cmitem"][data-id="${cmid}"]`).forEach(node => {
                    this.apply(node, descriptor);
                });
                this.scheduleScan();
                modal.hide();
                addToast(this.config.strings.saved, {type: 'success'});
            } catch (error) {
                picker.querySelectorAll('button').forEach(node => {
                    node.disabled = false;
                });
                Notification.exception(error);
            }
        });
        modal.show();
    }

    async fetch(cmids) {
        cmids.forEach(cmid => this.requested.add(cmid));
        try {
            const response = await Ajax.call([{
                methodname: 'local_activityicons_get_icons',
                args: {cmids: cmids.map(Number)},
            }])[0];
            Object.entries(JSON.parse(response.descriptorsjson)).forEach(([cmid, descriptor]) => {
                this.descriptors.set(cmid, descriptor);
            });
            this.scheduleScan();
        } catch (error) {
            // Dynamic lists are a best-effort enhancement. Retain Moodle's original icons when an
            // AJAX request is unavailable (for example while MFA requires a full-page redirect).
            this.dynamicFallbackAvailable = false;
        }
    }
}

export const init = config => {
    if (!instance) {
        instance = new ActivityIcons(config);
    }
};
