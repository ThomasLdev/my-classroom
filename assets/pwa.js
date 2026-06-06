// Registers the service worker (served from /sw.js at root scope). Only runs in
// a secure context (https or localhost); no-ops otherwise.
if ("serviceWorker" in navigator) {
    window.addEventListener("load", () => {
        navigator.serviceWorker.register("/sw.js").catch((error) => {
            console.error("Service worker registration failed:", error);
        });
    });
}
