import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["dialog"];
    static values = {
        peek: { type: Number, default: 55 },
        full: { type: Number, default: 80 },
        dismiss: { type: Number, default: 38 },
    };

    open() {
        if (this.dialogTarget.open) {
            return;
        }
        if (this.isCompact()) {
            this.setDetent(this.peekValue);
        } else {
            this.dialogTarget.style.height = ""; // centered modal: size to content
        }
        this.dialogTarget.showModal();
    }

    isCompact() {
        return !window.matchMedia("(min-width: 720px)").matches;
    }

    close() {
        this.dialogTarget.close();
    }

    // Open only once the session frame has its content (never an empty dialog
    // that would block the page while the request is in flight or cancelled).
    openIfSheet(event) {
        if (event.target && event.target.id === "sheet") {
            this.open();
        }
    }

    // Reload only the background sessions frame so the open sheet and backdrop stay in place.
    reloadSessions() {
        const frame = document.getElementById("day-sessions");
        if (!frame) {
            return;
        }
        if (frame.src) {
            frame.reload();
        } else {
            frame.src = window.location.href;
        }
    }

    backdrop(event) {
        if (event.target === this.dialogTarget) {
            this.dialogTarget.close();
        }
    }

    startDrag(event) {
        if (!this.hasDialogTarget || !this.isCompact()) {
            return; // drag-to-resize is a phone-only gesture, and needs the dialog
        }
        this.dragging = true;
        this.startY = event.clientY;
        this.startHeight = this.dialogTarget.getBoundingClientRect().height;
        this.dialogTarget.style.transition = "none";
        event.currentTarget.setPointerCapture(event.pointerId);
    }

    drag(event) {
        if (!this.dragging) {
            return;
        }
        const vh = window.innerHeight;
        const next = this.startHeight + (this.startY - event.clientY);
        const clamped = Math.max(vh * 0.18, Math.min(next, vh * 0.92));
        this.dialogTarget.style.height = `${clamped}px`;
    }

    endDrag() {
        if (!this.dragging) {
            return;
        }
        this.dragging = false;
        this.dialogTarget.style.transition = "";

        const vh = window.innerHeight;
        const height = this.dialogTarget.getBoundingClientRect().height;

        if (height < (vh * this.dismissValue) / 100) {
            this.dialogTarget.close();
            return;
        }

        const peek = (vh * this.peekValue) / 100;
        const full = (vh * this.fullValue) / 100;
        this.setDetent(
            Math.abs(height - full) < Math.abs(height - peek)
                ? this.fullValue
                : this.peekValue,
        );
    }

    setDetent(dvh) {
        this.dialogTarget.style.height = `${dvh}dvh`;
    }
}
