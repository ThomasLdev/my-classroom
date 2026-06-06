import { Controller } from "@hotwired/stimulus";

/* Drag-and-drop / click-to-browse upload. Submits the wrapping form (Turbo) as
   soon as a file is chosen, so there is no explicit "send" step. */
export default class extends Controller {
    static targets = ["input", "zone"];

    browse() {
        this.inputTarget.click();
    }

    selected() {
        if (this.inputTarget.files.length) {
            this.#submit();
        }
    }

    over(event) {
        event.preventDefault();
        if (this.hasZoneTarget) {
            this.zoneTarget.classList.add("is-over");
        }
    }

    leave() {
        if (this.hasZoneTarget) {
            this.zoneTarget.classList.remove("is-over");
        }
    }

    drop(event) {
        event.preventDefault();
        this.leave();
        if (!event.dataTransfer.files.length) {
            return;
        }
        this.inputTarget.files = event.dataTransfer.files;
        this.#submit();
    }

    #submit() {
        this.inputTarget.form.requestSubmit();
    }
}
