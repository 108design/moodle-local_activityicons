import Ajax from 'core/ajax';
import Modal from 'core/modal';
import Notification from 'core/notification';
import {add as addToast} from 'core/toast';

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
            try {
                const url = new URL(link.href, window.location.href);
                const isActivityView = url.origin === window.location.origin &&
                    /\/(?:mod)\/[^/]+\/view\.php$/.test(url.pathname);
                const cmid = isActivityView ? url.searchParams.get('id') : null;
                if (cmid && /^\d+$/.test(cmid)) {
                    addFoundRoot(found, cmid, link);
                }
            } catch (error) {
                // Ignore malformed third-party links.
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
                return !alreadyRenderedByCourse && !this.descriptors.has(cmid) && !this.requested.has(cmid);
            });
            if (unresolved.length) {
                this.fetch(unresolved.slice(0, 100));
            }
        }
    }

    imageIn(root) {
        const item = root.matches('[data-for="cmitem"]') ? root : root.closest('[data-for="cmitem"]');
        if (item) {
            return item.querySelector('img.activityicon, .activityiconcontainer img, img[src*="/mod/"][src*="icon"]');
        }

        const nested = root.querySelector('img.activityicon, img.moduleIcon, .activityiconcontainer img, img.icon, ' +
            'img[src*="/mod/"][src*="icon"]');
        if (nested || !root.matches('a')) {
            return nested;
        }

        // Some third-party renderers place the activity icon directly before the activity link.
        const previous = root.previousElementSibling;
        if (previous?.matches('img.activityicon, img.moduleIcon, img.icon')) {
            return previous;
        }
        if (previous?.childElementCount === 1) {
            return previous.querySelector('img.activityicon, img.moduleIcon, img.icon');
        }
        return null;
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
        const container = item.querySelector('.activityiconcontainer');
        if (!container || container.querySelector('[data-local-activityicons-edit]')) {
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
