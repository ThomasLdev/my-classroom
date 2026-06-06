import { Controller } from "@hotwired/stimulus";

/* Fires a one-shot window event on connect. Used to bridge a server-side change
   (e.g. a document upload handled by a Turbo form) back to listeners like the
   sheet, which reloads the day cards on `session:changed`. */
export default class extends Controller {
    static values = { event: String };

    connect() {
        window.dispatchEvent(new CustomEvent(this.eventValue));
    }
}
