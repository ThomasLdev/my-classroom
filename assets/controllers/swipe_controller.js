import { Controller } from '@hotwired/stimulus';

// Progressive enhancement: the gesture is additive; arrows and weekstrip stay accessible links.
export default class extends Controller {
    static values = {
        prevUrl: String,
        nextUrl: String,
        threshold: { type: Number, default: 40 },
    };

    connect() {
        this.startX = 0;
        this.startY = 0;
        this.tracking = false;

        this.onStart = this.onStart.bind(this);
        this.onEnd = this.onEnd.bind(this);
        this.element.addEventListener('touchstart', this.onStart, { passive: true });
        this.element.addEventListener('touchend', this.onEnd, { passive: true });
    }

    disconnect() {
        this.element.removeEventListener('touchstart', this.onStart);
        this.element.removeEventListener('touchend', this.onEnd);
    }

    onStart(event) {
        const touch = event.changedTouches[0];
        this.startX = touch.clientX;
        this.startY = touch.clientY;
        this.tracking = true;
    }

    onEnd(event) {
        if (!this.tracking) return;
        this.tracking = false;

        const touch = event.changedTouches[0];
        const dx = touch.clientX - this.startX;
        const dy = touch.clientY - this.startY;

        if (Math.abs(dx) < this.thresholdValue || Math.abs(dx) <= Math.abs(dy)) return;

        const url = dx < 0 ? this.nextUrlValue : this.prevUrlValue;
        if (!url) return;

        if (window.Turbo) {
            window.Turbo.visit(url);
        } else {
            window.location.assign(url);
        }
    }
}
