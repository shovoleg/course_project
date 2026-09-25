import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['query', 'category', 'results', 'selected', 'chosen', 'button'];
    static values = { url: String };

    connect() {
        this._lastPickedName = '';
        this._updateButton();
        this.search();
    }

    search() {
        if (this.hasChosenTarget && this.hasQueryTarget) {
            const q = this.queryTarget.value;
            if (q !== this._lastPickedName) {
                this.chosenTarget.value = '';
                this._lastPickedName = '';
                this._updateButton();
            }
        }
        const params = new URLSearchParams({
            q: this.hasQueryTarget ? this.queryTarget.value : '',
            category: this.hasCategoryTarget ? this.categoryTarget.value : '',
        });
        fetch(this.urlValue + '?' + params.toString())
            .then((response) => response.json())
            .then((data) => this.render(data.items || []))
            .catch(() => this.render([]));
    }

    render(items) {
        if (!this.hasResultsTarget) {
            return;
        }
        this.resultsTarget.innerHTML = '';
        items.forEach((item) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'list-group-item list-group-item-action';
            button.textContent = item.name;
            button.addEventListener('click', () => this.pick(item));
            this.resultsTarget.appendChild(button);
        });
    }

    pick(item) {
        if (this.hasChosenTarget) {
            this.chosenTarget.value = String(item.id);
            this._lastPickedName = item.name;
            if (this.hasQueryTarget) {
                this.queryTarget.value = item.name;
            }
            this._updateButton();
            if (this.hasResultsTarget) {
                this.resultsTarget.innerHTML = '';
            }
            return;
        }
        this.add(item);
    }

    add(item) {
        if (!this.hasSelectedTarget || this.selectedTarget.querySelector('[data-id="' + item.id + '"]')) {
            return;
        }
        const row = document.createElement('div');
        row.className = 'd-flex justify-content-between align-items-center border rounded px-2 py-1 mb-1';
        row.dataset.id = item.id;
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'attribute_ids[]';
        input.value = item.id;
        const label = document.createElement('span');
        label.textContent = item.name;
        const remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'btn btn-sm btn-outline-secondary';
        remove.textContent = '×';
        remove.addEventListener('click', () => row.remove());
        row.append(input, label, remove);
        this.selectedTarget.appendChild(row);
    }

    remove(event) {
        event.target.closest('[data-id]')?.remove();
    }

    _updateButton() {
        if (!this.hasButtonTarget || !this.hasChosenTarget) {
            return;
        }
        const hasValue = this.chosenTarget.value !== '';
        this.buttonTarget.disabled = !hasValue;
    }
}
