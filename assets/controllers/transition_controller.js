import { Controller } from '@hotwired/stimulus';

/*
 * Tags the next Turbo navigation with a direction (next / prev) based on the
 * calendar date in the URL, so the View Transition can slide accordingly.
 * Direction-less navigations fall back to the default cross-fade.
 */
export default class extends Controller {
    connect() {
        this.onBeforeVisit = this.onBeforeVisit.bind(this);
        document.addEventListener('turbo:before-visit', this.onBeforeVisit);
    }

    disconnect() {
        document.removeEventListener('turbo:before-visit', this.onBeforeVisit);
    }

    onBeforeVisit(event) {
        const current = this.dateIn(window.location.pathname);
        const target = this.dateIn(new URL(event.detail.url, window.location.origin).pathname);
        const root = document.documentElement;

        if (current && target && target !== current) {
            root.dataset.transition = target > current ? 'next' : 'prev';
        } else {
            delete root.dataset.transition;
        }
    }

    dateIn(pathname) {
        const match = pathname.match(/\/calendar\/(\d{4}-\d{2}-\d{2})/);
        return match ? match[1] : null;
    }
}
