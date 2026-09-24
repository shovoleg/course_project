import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['list'];
    static values = { url: String, after: Number };

    connect() {
        this.timer = window.setInterval(() => this.poll(), 3000);
    }

    disconnect() {
        window.clearInterval(this.timer);
    }

    async poll() {
        if (document.hidden) {
            return;
        }
        const response = await fetch(this.urlValue + '?after=' + this.afterValue, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });
        if (!response.ok) {
            return;
        }
        const html = await response.text();
        if (html.trim() === '') {
            return;
        }
        this.listTarget.insertAdjacentHTML('beforeend', html);
        const articles = this.listTarget.querySelectorAll('article[data-id]');
        const last = articles[articles.length - 1];
        if (last) {
            this.afterValue = Number(last.dataset.id);
        }
        const empty = this.listTarget.querySelector('[data-discussion-target="empty"]');
        if (empty && articles.length) {
            empty.remove();
        }
    }
}
