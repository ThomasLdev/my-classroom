import { Controller } from '@hotwired/stimulus';

// Drag the selection pill across the week to scrub days; releasing navigates to the
// day under it. Progressive enhancement: the cells stay plain links, so the strip
// works without JS and a click still navigates.
export default class extends Controller {
    static targets = ['pill'];

    connect() {
        this.cells = [...this.element.querySelectorAll('.weekstrip__cell')];
        this.selectedIndex = this.cells.findIndex((c) => c.classList.contains('weekstrip__cell--selected'));
        if (this.selectedIndex < 0) this.selectedIndex = 0;

        this.dragging = false;
        this.moved = false;

        this.onPointerDown = this.onPointerDown.bind(this);
        this.onPointerMove = this.onPointerMove.bind(this);
        this.onPointerUp = this.onPointerUp.bind(this);
        this.onTouchStart = this.onTouchStart.bind(this);
        this.suppressClick = (event) => { event.preventDefault(); event.stopPropagation(); };
        this.preventDefault = (event) => event.preventDefault();

        this.element.addEventListener('pointerdown', this.onPointerDown);
        this.element.addEventListener('dragstart', this.preventDefault); // kill native link ghost-drag
        // Keep the parent swipe controller from also reacting to a pill drag.
        this.element.addEventListener('touchstart', this.onTouchStart, { capture: true });
    }

    disconnect() {
        this.element.removeEventListener('pointerdown', this.onPointerDown);
        this.element.removeEventListener('dragstart', this.preventDefault);
        this.element.removeEventListener('touchstart', this.onTouchStart, { capture: true });
    }

    onTouchStart(event) {
        if (event.target.closest('.weekstrip__cell--selected')) event.stopPropagation();
    }

    onPointerDown(event) {
        if (event.pointerType === 'mouse' && event.button !== 0) return;
        // The pill is the only handle: a drag must start on the currently selected day.
        if (!event.target.closest('.weekstrip__cell--selected')) return;

        event.preventDefault();
        this.dragging = true;
        this.moved = false;
        this.pointerId = event.pointerId;
        this.startX = event.clientX;
        this.targetIndex = this.selectedIndex;
        this.maxIndex = this.cells.length - 1;

        const first = this.cells[0].getBoundingClientRect();
        const second = this.cells[1].getBoundingClientRect();
        this.step = second.left - first.left; // column width + gap
        this.baseTranslate = this.selectedIndex * this.step;

        this.element.setPointerCapture(this.pointerId);
        this.element.classList.add('weekstrip--dragging');
        // Keep the dark circle on the current day from the first frame — no flicker.
        this.cells[this.selectedIndex].classList.add('weekstrip__cell--target');
        this.element.addEventListener('pointermove', this.onPointerMove);
        this.element.addEventListener('pointerup', this.onPointerUp);
        this.element.addEventListener('pointercancel', this.onPointerUp);
    }

    onPointerMove(event) {
        if (!this.dragging) return;
        const dx = event.clientX - this.startX;
        if (Math.abs(dx) > 3) this.moved = true;

        const translate = Math.max(0, Math.min(this.maxIndex * this.step, this.baseTranslate + dx));
        this.pillTarget.style.transform = `translateX(${translate}px)`;

        const nearest = Math.round(translate / this.step);
        if (nearest !== this.targetIndex) {
            this.targetIndex = nearest;
            this.cells.forEach((cell, i) => cell.classList.toggle('weekstrip__cell--target', i === nearest));
        }
    }

    onPointerUp() {
        if (!this.dragging) return;
        this.dragging = false;
        this.element.classList.remove('weekstrip--dragging');
        this.cells.forEach((cell) => cell.classList.remove('weekstrip__cell--target'));
        this.element.removeEventListener('pointermove', this.onPointerMove);
        this.element.removeEventListener('pointerup', this.onPointerUp);
        this.element.removeEventListener('pointercancel', this.onPointerUp);

        // A real drag must not also fire the handle's click.
        if (this.moved) this.element.addEventListener('click', this.suppressClick, { capture: true, once: true });

        if (this.moved && this.targetIndex !== this.selectedIndex) {
            // Leave the pill where it was dropped; the view transition slides it onto the new day.
            const url = this.cells[this.targetIndex].href;
            if (window.Turbo) window.Turbo.visit(url);
            else window.location.assign(url);
        } else {
            this.pillTarget.style.transform = ''; // snap back to the current day
        }
    }
}
