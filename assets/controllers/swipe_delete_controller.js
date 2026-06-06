import { Controller } from '@hotwired/stimulus';

/* Native-style swipe-to-delete (à la Gmail mobile): drag a row left to reveal a
   red delete zone; past the threshold, release deletes. The actual deletion is
   delegated to a hidden LiveComponent action trigger. */
const DIRECTION_LOCK = 6; // px before we decide horizontal vs vertical
const MAX_TRAVEL = 0.25;  // the row can move at most ~a quarter of its width
const THRESHOLD = 0.18;   // commit the delete once dragged past ~70% of that travel

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
        // NB: no setPointerCapture here — capturing on a plain click retargets the
        // click to the <li> and the activity's markDone never fires. We capture only
        // once a horizontal drag is confirmed (see move()).
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
            // Confirmed horizontal drag: capture the pointer and freeze the surface.
            this.element.setPointerCapture(event.pointerId);
            this.surfaceTarget.style.transition = 'none';
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
            this.commitRemoval();
            return;
        }

        this.snapBack();
    }

    // Collapse the row (height + fade) before asking the server to remove it, so the
    // list closes the gap smoothly instead of the row vanishing abruptly.
    commitRemoval() {
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            this.triggerTarget.click();
            return;
        }
        // Pin the current height, then animate it (and margin/opacity) to 0 so the
        // row collapses fully and the list closes the gap in one motion.
        this.element.style.blockSize = `${this.element.offsetHeight}px`;
        this.element.getBoundingClientRect(); // force reflow so the next change transitions
        this.element.classList.add('is-removing');
        this.element.style.blockSize = '0';
        setTimeout(() => this.triggerTarget.click(), 260);
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
