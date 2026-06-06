// Turbo 8 awaits ViewTransition.finished without catching it; an aborted/skipped
// transition (overlapping navigations) then surfaces as an uncaught rejection.
// It is benign — the navigation still completes — so swallow only that one.
window.addEventListener('unhandledrejection', (event) => {
    const reason = event.reason;
    if (
        reason instanceof DOMException
        && (reason.name === 'InvalidStateError' || reason.name === 'AbortError')
        && /transition/i.test(reason.message)
    ) {
        event.preventDefault();
    }
});
