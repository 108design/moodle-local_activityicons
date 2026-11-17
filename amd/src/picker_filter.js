// This file is part of Moodle - https://moodle.org/
/**
 * This file is part of a 108design source-available software product.
 *
 * Use and modification are permitted only under the Software License included
 * with this distribution. See LICENSE.md for the full terms.
 *
 * File documentation for amd/src/picker_filter.js.
 *
 * @package   local_activityicons
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license   See LICENSE.md for the full terms.
 */
const normalize = text => text.toLocaleLowerCase().replace(/ß/g, 'ss').normalize('NFD').replace(/\p{M}/gu, '');

const field = (title, control) => {
    const label = document.createElement('label');
    label.className = 'd-block mb-0';
    label.textContent = title;
    label.append(control);
    return label;
};

/**
 * Create accessible filter controls for a picker. Pool controls are only used for icon choices.
 *
 * @param {Object} strings Translated labels.
 * @param {Object} pools Pool keys and labels.
 * @returns {HTMLElement}
 */
export const controls = (strings, pools = {}) => {
    const filters = document.createElement('div');
    filters.className = 'local-activityicons-filters mb-3';
    if (Object.keys(pools).length) {
        const select = document.createElement('select');
        select.className = 'form-select custom-select w-100';
        select.dataset.iconPool = '';
        select.add(new Option(strings.allPools, ''));
        for (const [key, label] of Object.entries(pools)) {
            select.add(new Option(label, key));
        }
        filters.append(field(strings.pool, select));
    }
    const search = document.createElement('input');
    search.className = 'form-control';
    search.type = 'search';
    search.dataset.iconSearch = '';
    filters.append(field(strings.search, search));
    if (strings.noResults) {
        const empty = document.createElement('p');
        empty.className = 'text-muted mb-0';
        empty.dataset.iconEmpty = '';
        empty.textContent = strings.noResults;
        empty.setAttribute('role', 'status');
        empty.hidden = true;
        filters.append(empty);
    }
    return filters;
};

/**
 * Make a choice filterable. Items without filter metadata stay visible.
 *
 * @param {HTMLElement} choice A choice button.
 * @param {Object} definition A catalog entry.
 */
export const markChoice = (choice, definition) => {
    choice.dataset.search = normalize(`${definition.label} ${definition.search || ''}`);
    choice.dataset.pools = [definition.pool, ...(definition.additionalPools || [])].filter(Boolean).join(' ');
};

/**
 * Combine search and pool filters on the actual modal DOM.
 *
 * @param {HTMLElement} root Modal root.
 * @param {HTMLElement} grid Choice grid.
 */
export const bind = (root, grid) => {
    const search = root.querySelector('[data-icon-search]');
    const pool = root.querySelector('[data-icon-pool]');
    const empty = root.querySelector('[data-icon-empty]');
    const choices = Array.from(grid.querySelectorAll('[data-search]'));
    const update = () => {
        const words = normalize(search.value).trim().split(/\s+/).filter(Boolean);
        let matches = 0;
        for (const choice of choices) {
            const inPool = !pool?.value || choice.dataset.pools.split(' ').includes(pool.value);
            const visible = inPool && words.every(word => choice.dataset.search.includes(word));
            choice.hidden = !visible;
            matches += Number(visible);
        }
        if (empty) {
            empty.hidden = matches > 0;
        }
    };
    search.addEventListener('input', update);
    pool?.addEventListener('change', update);
};
