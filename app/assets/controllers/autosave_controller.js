import { Controller } from '@hotwired/stimulus';
import { issueCsrfToken } from './csrf_protection_controller.js';

export default class extends Controller {
    static targets = ['status', 'projects', 'projectEmpty', 'projectBlueprint', 'projectRows', 'projectDelete'];
    static values = { url: String, saved: String, conflict: String, unnamed: String };

    connect() {
        this.dirty = false;
        this.saving = false;
        this.timer = 0;
        this.element.addEventListener('input', () => this.touch());
        this.element.addEventListener('change', () => this.touch());
        this.element.addEventListener('submit', (event) => this.beforeSubmit(event));
        this.refreshEmpty();
        this.syncProjects();
    }

    disconnect() {
        window.clearTimeout(this.timer);
    }

    touch() {
        this.dirty = true;
        this.refreshEmpty();
        this.syncProjects();
        window.clearTimeout(this.timer);
        this.timer = window.setTimeout(() => this.save(), 800);
    }

    saveNow(event) {
        event.preventDefault();
        this.dirty = true;
        this.save();
    }

    addProject(event) {
        event.preventDefault();
        const cards = [...this.projectsTarget.querySelectorAll('[data-project-id]')];
        const last = cards[cards.length - 1];
        const lastName = last ? last.querySelector('[data-field="name"]') : null;
        if (lastName && lastName.value.trim() === '') {
            lastName.focus();
            return;
        }
        const card = this.projectBlueprintTarget.content.firstElementChild.cloneNode(true);
        const key = 'new-' + Date.now();
        card.dataset.clientKey = key;
        this.projectsTarget.appendChild(card);
        this.projectEmptyTarget.classList.add('d-none');
        this.projectDeleteTarget.classList.remove('d-none');
        this.ensureRow(key, '');
        const name = card.querySelector('[data-field="name"]');
        if (name) {
            name.focus();
        }
    }

    async beforeSubmit(event) {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || form.dataset.autosaveReady === '1' || (!this.dirty && !this.saving)) {
            return;
        }
        event.preventDefault();
        this.dirty = true;
        let ok = await this.save();
        while (this.saving) {
            await new Promise((resolve) => window.setTimeout(resolve, 50));
        }
        if (this.dirty) {
            ok = await this.save();
        }
        if (!ok || this.dirty) {
            return;
        }
        const selected = form.querySelector('[data-table-toolbar-target="row"]:checked');
        const version = form.querySelector('[name="version"]');
        if (selected && version) {
            version.value = selected.dataset.version || '';
        }
        form.dataset.autosaveReady = '1';
        form.requestSubmit();
    }

    async save() {
        if (!this.dirty || this.saving) {
            return !this.dirty;
        }
        window.clearTimeout(this.timer);
        this.saving = true;
        this.dirty = false;
        try {
            const response = await fetch(this.urlValue, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': issueCsrfToken(),
                },
                body: JSON.stringify(this.payload()),
            });
            if (response.status === 409) {
                const data = await response.json();
                this.statusTarget.textContent = data.message || this.conflictValue;
                return false;
            }
            if (!response.ok) {
                this.dirty = true;
                this.timer = window.setTimeout(() => this.save(), 3000);
                return false;
            }
            const data = await response.json();
            this.apply(data);
            const errors = data.errors || [];
            this.mark(errors);
            this.refreshEmpty();
            this.syncProjects();
            this.statusTarget.textContent = errors.length
                ? errors.map((error) => error.message).join(' ')
                : this.savedValue;
            return errors.length === 0;
        } catch (error) {
            this.dirty = true;
            this.timer = window.setTimeout(() => this.save(), 3000);
            return false;
        } finally {
            this.saving = false;
            if (this.dirty) {
                this.timer = window.setTimeout(() => this.save(), 300);
            }
        }
    }

    payload() {
        return {
            values: [...this.element.querySelectorAll('[data-value-id]')].map((node) => this.valuePayload(node)),
            projects: [...this.element.querySelectorAll('[data-project-id]')].map((node) => this.projectPayload(node)),
        };
    }

    valuePayload(node) {
        const read = (name) => {
            const field = node.querySelector('[data-field="' + name + '"]');
            if (!field) {
                return null;
            }
            if (field.type === 'checkbox') {
                return field.checked ? '1' : '0';
            }
            return field.value;
        };
        return {
            attributeId: Number(node.dataset.valueId),
            version: Number(node.dataset.version || 0),
            string: read('string'),
            text: read('text'),
            numeric: read('numeric'),
            date: read('date'),
            periodStart: read('periodStart'),
            periodEnd: read('periodEnd'),
            boolean: read('boolean'),
            optionId: read('optionId'),
            imageUrl: read('imageUrl'),
            imagePublicId: read('imagePublicId'),
        };
    }

    projectPayload(node) {
        const tags = node.querySelector('[data-field="tags"]');
        return {
            id: Number(node.dataset.projectId || 0),
            clientKey: node.dataset.clientKey || '',
            version: Number(node.dataset.version || 0),
            name: node.querySelector('[data-field="name"]')?.value || '',
            description: node.querySelector('[data-field="description"]')?.value || '',
            periodStart: node.querySelector('[data-field="periodStart"]')?.value || '',
            periodEnd: node.querySelector('[data-field="periodEnd"]')?.value || '',
            tags: tags ? [...tags.selectedOptions].map((option) => option.value) : [],
        };
    }

    refreshEmpty() {
        this.element.querySelectorAll('[data-value-id]').forEach((node) => {
            const empty = this.isEmpty(node);
            node.classList.toggle('cv-empty', empty);
            node.classList.toggle('rounded', empty);
            node.classList.toggle('p-2', empty);
        });
    }

    isEmpty(node) {
        const read = (name) => {
            const field = node.querySelector('[data-field="' + name + '"]');
            return field ? field.value.trim() : '';
        };
        const type = node.dataset.type || '';
        if (type === 'string' || type === 'text' || type === 'numeric' || type === 'date') {
            return read(type) === '';
        }
        if (type === 'period') {
            return read('periodStart') === '' || read('periodEnd') === '';
        }
        if (type === 'boolean') {
            return read('boolean') === '';
        }
        if (type === 'one_of_many') {
            return read('optionId') === '';
        }
        if (type === 'image') {
            return read('imageUrl') === '';
        }
        return false;
    }

    syncProjects() {
        this.element.querySelectorAll('[data-project-id]').forEach((node) => {
            const name = node.querySelector('[data-field="name"]');
            this.ensureRow(node.dataset.clientKey || '', name ? name.value.trim() : '');
        });
    }

    ensureRow(key, label) {
        if (!key || !this.hasProjectRowsTarget) {
            return;
        }
        let row = this.projectRowsTarget.querySelector('[data-client-key="' + key + '"]');
        if (!row) {
            row = document.createElement('tr');
            row.dataset.clientKey = key;
            row.innerHTML = '<td style="width: 2rem"><input type="radio" name="selected" value="" data-version="0" data-table-toolbar-target="row" data-action="change->table-toolbar#refresh" disabled></td><td data-project-label></td>';
            this.projectRowsTarget.appendChild(row);
        }
        const cell = row.querySelector('[data-project-label]');
        if (cell) {
            cell.textContent = label || this.unnamedValue;
        }
    }

    mark(errors) {
        this.element.querySelectorAll('.is-invalid').forEach((field) => field.classList.remove('is-invalid'));
        errors.forEach((error) => {
            const node = error.attributeId
                ? this.element.querySelector('[data-value-id="' + error.attributeId + '"]')
                : this.element.querySelector('[data-project-id][data-client-key="' + error.clientKey + '"]');
            const field = node ? node.querySelector('input, textarea, select') : null;
            if (field) {
                field.classList.add('is-invalid');
            }
        });
    }

    apply(data) {
        (data.values || []).forEach((row) => {
            const node = this.element.querySelector('[data-value-id="' + row.attributeId + '"]');
            if (node) {
                node.dataset.version = row.version;
            }
        });
        (data.projects || []).forEach((row) => {
            const node = this.element.querySelector('[data-project-id][data-client-key="' + row.clientKey + '"]');
            if (node) {
                node.dataset.projectId = row.id;
                node.dataset.version = row.version;
            }
            if (!this.hasProjectRowsTarget) {
                return;
            }
            const listRow = this.projectRowsTarget.querySelector('[data-client-key="' + row.clientKey + '"]');
            const radio = listRow ? listRow.querySelector('input') : null;
            if (radio) {
                radio.value = row.id;
                radio.dataset.version = row.version;
                radio.disabled = false;
            }
        });
    }
}
