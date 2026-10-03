import Modal from 'core/modal';
import Notification from 'core/notification';

const node = (tag, classes, text = '') => {
    const element = document.createElement(tag);
    element.className = classes;
    element.textContent = text;
    return element;
};

/**
 * Enhance one native field without changing its submission or validation contract.
 * @param {HTMLSelectElement} select Native form field.
 * @param {Object} config Choices and translated labels.
 */
const enhance = (select, config) => {
    if (select.dataset.activityiconsEnhanced) {
        return;
    }
    select.dataset.activityiconsEnhanced = '1';
    select.classList.add('local-activityicons-mapping-select');
    const trigger = node('button', config.type ? 'btn local-activityicons-type-trigger' :
        'btn btn-outline-secondary local-activityicons-mapping-trigger');
    trigger.type = 'button';
    trigger.setAttribute('aria-haspopup', 'dialog');
    const update = () => {
        trigger.replaceChildren();
        const selected = config.catalog.find(icon => icon.key === select.value);
        if (selected?.url) {
            const image = node('img', 'local-activityicons-preview');
            image.src = selected.url;
            image.alt = '';
            image.width = 24;
            image.height = 24;
            trigger.append(image);
        }
        trigger.append(node('span', '', selected ? selected.label : config.choose));
        trigger.setAttribute('aria-label', selected ? `${config.choose}: ${selected.label}` : config.choose);
        trigger.title = selected ? selected.label : config.choose;
    };
    update();
    select.addEventListener('change', update);
    select.hidden = true;
    select.after(trigger);
    trigger.addEventListener('click', async() => {
        try {
            const body = node('div', '');
            const label = node('label', 'w-100', config.search);
            const search = node('input', 'form-control mb-3');
            search.type = 'search';
            search.dataset.mappingSearch = '1';
            label.append(search);
            body.append(label);
            const grid = node('div', config.type ? 'local-activityicons-type-picker' : 'local-activityicons-picker');
            grid.dataset.mappingChoices = '1';
            config.catalog.forEach(icon => {
                const button = node('button', config.type ? 'btn local-activityicons-type-choice' :
                    'btn local-activityicons-choice');
                button.type = 'button';
                button.dataset.iconkey = icon.key;
                button.dataset.search = `${icon.label} ${icon.search || ''}`.toLocaleLowerCase();
                button.setAttribute('aria-pressed', String(select.value === icon.key));
                if (icon.url) {
                    const image = node('img', 'activityicon');
                    image.src = icon.url;
                    image.alt = '';
                    button.append(image);
                }
                button.append(node('span', 'local-activityicons-choice-label', icon.label));
                if (icon.detail) {
                    button.append(node('small', 'text-muted', icon.detail));
                }
                grid.append(button);
            });
            body.append(grid);
            const close = node('button', 'btn btn-primary', config.close);
            close.type = 'button';
            close.dataset.mappingClose = '1';
            const modal = await Modal.create({title: config.choose, body: body.outerHTML,
                footer: close.outerHTML, returnElement: trigger, removeOnClose: true});
            const root = modal.getRoot()[0];
            root.querySelector('[data-mapping-close]').addEventListener('click', () => modal.hide());
            root.querySelector('[data-mapping-search]').addEventListener('input', event => {
                const query = event.target.value.trim().toLocaleLowerCase();
                root.querySelectorAll('[data-iconkey]').forEach(button => {
                    button.hidden = !button.dataset.search.includes(query);
                });
            });
            root.querySelector('[data-mapping-choices]').addEventListener('click', event => {
                const choice = event.target.closest('[data-iconkey]');
                if (!choice) {
                    return;
                }
                select.value = choice.dataset.iconkey;
                select.dispatchEvent(new Event('change', {bubbles: true}));
                modal.hide();
            });
            modal.show();
        } catch (error) {
            Notification.exception(error);
        }
    });
};

/**
 * Progressive enhancement: native selects remain usable if this module cannot load.
 * @param {Object} config Catalog and translated labels.
 */
export const init = config => {
    const deleteButton = document.querySelector('[name="deleteiconsubmit"]');
    if (deleteButton) {
        const selections = Array.from(document.querySelectorAll('input[name^="deleteicons["]:not(:disabled)'));
        const updateDeleteSelection = () => {
            const selected = selections.filter(checkbox => checkbox.checked);
            selections.forEach(checkbox => checkbox.closest('.local-activityicons-pool-card')
                .classList.toggle('is-delete-selected', checkbox.checked));
            deleteButton.disabled = selected.length === 0;
            deleteButton.value = selected.length ? `${config.deleteSelected} (${selected.length})` : config.deleteSelected;
        };
        selections.forEach(checkbox => checkbox.addEventListener('change', updateDeleteSelection));
        updateDeleteSelection();
    }
    // Moodle templates may discard custom classes; repeated field names are stable.
    document.querySelectorAll('select[name^="mappingicon["]').forEach(select => enhance(select, config));
    document.querySelectorAll('select[name^="machine["]').forEach(select => {
        const catalog = Array.from(select.options).filter(option => option.value).map(option => ({
            key: option.value,
            label: option.text.replace(/\s+\(H5P\.[^)]+\)$/, '').replace(/^H5P\./, ''),
            detail: option.value,
            search: option.text,
        }));
        enhance(select, {...config, type: true, catalog, choose: config.chooseType, search: config.chooseType});
    });
    const container = document.querySelector('.local-activityicons-mappings');
    if (!container || container.dataset.enhanced) {
        return;
    }
    container.dataset.enhanced = '1';
    const rows = Array.from(container.querySelectorAll('select[name^="machine["]')).map(machine => {
        const index = machine.name.match(/\[(\d+)\]/)[1];
        const icon = container.querySelector(`select[name="mappingicon[${index}]"]`);
        const row = machine.closest('[data-groupname]');
        // Moodle renders hidden fields at form level, not necessarily beside the repeated group.
        const deleted = machine.form.elements.namedItem(`mappingdeleted[${index}]`);
        const remove = row.querySelector(`[name="removemapping[${index}]"]`);
        remove.type = 'button';
        const update = () => {
            const marked = deleted.value === '1';
            row.classList.toggle('is-pending-removal', marked);
            row.classList.toggle('is-incomplete', !marked && (!machine.value || !icon.value));
            machine.nextElementSibling.disabled = marked;
            icon.nextElementSibling.disabled = marked;
            remove.value = marked ? '↶' : '×';
            remove.title = marked ? config.undoRemove : config.remove;
            remove.setAttribute('aria-label', remove.title);
            remove.setAttribute('aria-pressed', String(marked));
        };
        remove.addEventListener('click', event => {
            event.preventDefault();
            deleted.value = deleted.value === '1' ? '0' : '1';
            update();
        });
        machine.addEventListener('change', update);
        icon.addEventListener('change', update);
        update();
        return {index, machine, icon, row, deleted, remove};
    });
    const focus = item => {
        const field = !item.machine.value ? item.machine : item.icon;
        field.nextElementSibling.focus({preventScroll: true});
        const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        item.row.scrollIntoView({block: 'center', behavior: reducedMotion ? 'instant' : 'smooth'});
        item.animation?.cancel();
        if (!reducedMotion) {
            item.animation = item.row.animate([
                {boxShadow: 'inset 0 0 0 2px transparent'},
                {boxShadow: 'inset 0 0 0 2px var(--bs-primary, #0f6cbf)'},
                {boxShadow: 'inset 0 0 0 2px transparent'},
            ], {duration: 900, iterations: 1});
        }
    };
    const add = container.querySelector('[name="addmapping"]');
    add.addEventListener('click', event => {
        const pending = rows.find(item => item.deleted.value !== '1' && (!item.machine.value || !item.icon.value));
        if (pending) {
            event.preventDefault();
            focus(pending);
        }
    });
    if (container.dataset.focusRow !== '') {
        const target = rows.find(item => item.index === container.dataset.focusRow);
        if (target) {
            requestAnimationFrame(() => focus(target));
        }
    }
    const toggled = rows.find(item => item.index === container.dataset.toggleRow);
    if (toggled) {
        requestAnimationFrame(() => toggled.remove.focus());
    }
};
