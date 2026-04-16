import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['modal', 'overlay'];

    connect() {
        this.closeOnEscape = this.closeOnEscape.bind(this);
        document.addEventListener('keydown', this.closeOnEscape);
    }

    disconnect() {
        document.removeEventListener('keydown', this.closeOnEscape);
    }

    open(event) {
        if (event) {
            event.preventDefault();
        }
        this.modalTarget.classList.remove('hidden');
        this.overlayTarget.classList.remove('hidden');

        // Focus first input
        const firstInput = this.modalTarget.querySelector('input');
        if (firstInput) {
            firstInput.focus();
        }
    }

    close(event) {
        if (event) {
            event.preventDefault();
        }
        this.modalTarget.classList.add('hidden');
        this.overlayTarget.classList.add('hidden');
    }

    closeOnEscape(event) {
        if (event.key === 'Escape') {
            this.close();
        }
    }
}
