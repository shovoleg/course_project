import { Controller } from '@hotwired/stimulus';
import TomSelect from 'tom-select';

export default class extends Controller {
    static values = { url: String };

    connect() {
        this.select = new TomSelect(this.element, {
            persist: false,
            create: true,
            plugins: ['remove_button'],
            load: (query, callback) => {
                if (!query) {
                    callback();
                    return;
                }
                fetch(this.urlValue + '?q=' + encodeURIComponent(query))
                    .then((response) => response.json())
                    .then((data) => callback((data.items || []).map((name) => ({ value: name, text: name }))))
                    .catch(() => callback());
            },
        });
    }

    disconnect() {
        this.select.destroy();
    }
}
