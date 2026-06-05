import { Controller } from '@hotwired/stimulus';

/* Native-style swipe-to-delete (à la Gmail mobile): drag a row left to reveal a
   red delete zone; past the threshold, release deletes. The actual deletion is
   delegated to a hidden LiveComponent action trigger. */
const DIRECTION_LOCK = 6; // px before we decide horizontal vs vertical
const THRESHOLD = 0.45;   // fraction of the row width that commits the delete
const MAX_TRAVEL = 0.9;   // clamp how far the row can travel

export default class extends Controller {
    static targets = ['surface', 'trigger'];

    start(event) {
        if (event.pointerType === 'mouse' && event.button !== 0) {
            return;
        }
        this.startX = event.clientX;
        this.startY = event.clientY;
        this.dragging = true;
        this.axis = null;
        this.moved = false;
        this.dx = 0;
        this.surfaceTarget.style.transition = 'none';
        this.element.setPointerCapture(event.pointerId);
    }

    move(event) {
        if (!this.dragging) {
            return;
        }

        const dx = event.clientX - this.startX;
        const dy = event.clientY - this.startY;

        if (this.axis === null) {
            if (Math.abs(dx) < DIRECTION_LOCK && Math.abs(dy) < DIRECTION_LOCK) {
                return;
            }
            this.axis = Math.abs(dx) > Math.abs(dy) ? 'x' : 'y';
            if (this.axis === 'y') {
                this.dragging = false; // let the sheet scroll
                return;
            }
        }

        event.preventDefault();
        this.moved = true;
        this.dx = Math.min(0, Math.max(dx, -this.width() * MAX_TRAVEL));
        this.surfaceTarget.style.transform = `translateX(${this.dx}px)`;
        this.element.classList.toggle('is-armed', Math.abs(this.dx) >= this.width() * THRESHOLD);
    }

    end() {
        if (!this.dragging) {
            this.snapBack();
            return;
        }
        this.dragging = false;
        this.surfaceTarget.style.transition = '';

        if (Math.abs(this.dx) >= this.width() * THRESHOLD) {
            this.surfaceTarget.style.transform = 'translateX(-100%)';
            this.triggerTarget.click(); // fires the LiveComponent deleteActivity action
            return;
        }

        this.snapBack();
    }

    // Swallow the click that follows a real drag so it doesn't toggle the activity.
    tap(event) {
        if (this.moved) {
            event.preventDefault();
            event.stopImmediatePropagation();
            this.moved = false;
        }
    }

    snapBack() {
        this.surfaceTarget.style.transform = '';
        this.element.classList.remove('is-armed');
    }

    width() {
        return this.element.offsetWidth || 1;
    }
}
