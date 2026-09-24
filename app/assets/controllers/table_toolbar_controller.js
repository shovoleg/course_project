import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['row', 'needsSelection'];

    connect() {
        this.refresh();
    }

    refresh() {
        const selected = this.selected();
        this.needsSelectionTargets.forEach((button) => {
            button.disabled = !selected;
        });
    }

    selected() {
        return this.rowTargets.find((row) => row.checked) || null;
    }

    open(event) {
        const row = this.selected();
        if (!row) {
            return;
        }
        window.location = event.params.url.replace('__id__', row.value);
    }

    assign(event) {
        const row = this.selected();
        const form = event.target.closest('form');
        if (!row || !form) {
            event.preventDefault();
            return;
        }
        if (form.dataset.confirm && !window.confirm(form.dataset.confirm)) {
            event.preventDefault();
            return;
        }
        if (form.dataset.actionTemplate) {
            form.action = form.dataset.actionTemplate.replace('__id__', row.value);
        }
        const version = form.querySelector('[name="version"]');
        if (version) {
            version.value = row.dataset.version || '';
        }
    }
}
