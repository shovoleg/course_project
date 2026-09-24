import { Controller } from '@hotwired/stimulus';
import { issueCsrfToken } from './csrf_protection_controller.js';

export default class extends Controller {
    static targets = ['preview', 'url', 'publicId', 'status'];
    static values = { uploadUrl: String };

    dragover(event) {
        event.preventDefault();
    }

    drop(event) {
        event.preventDefault();
        const file = event.dataTransfer.files[0];
        if (file) {
            this.send(file);
        }
    }

    choose(event) {
        const file = event.target.files[0];
        if (file) {
            this.send(file);
        }
    }

    async send(file) {
        this.statusTarget.textContent = '';
        try {
            const body = new FormData();
            body.append('file', file);
            const response = await fetch(this.uploadUrlValue, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': issueCsrfToken() },
                body,
            });
            const data = await response.json();
            if (!response.ok || !data.url) {
                this.statusTarget.textContent = data.error || this.element.dataset.failed || '';
                return;
            }
            this.urlTarget.value = data.url;
            this.publicIdTarget.value = data.publicId || '';
            this.previewTarget.src = data.url;
            this.previewTarget.classList.remove('d-none');
            this.statusTarget.textContent = '';
            const field = this.element.closest('[data-value-id]');
            if (field) {
                field.classList.remove('cv-empty');
            }
            this.urlTarget.dispatchEvent(new Event('input', { bubbles: true }));
        } catch (error) {
            this.statusTarget.textContent = this.element.dataset.failed || '';
        }
    }
}
