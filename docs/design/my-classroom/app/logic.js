/* logic.js — fonctions pures de calcul (séances + report d'activités).
   Dépend de window.DATA. Exposé sur window.LOGIC. */
(function () {
  const D = window.DATA;

  // Vue d'une séance : activités propres + activités reportées de la séance précédente.
  function sessionView(session, store) {
    const sd = store[session.id] || D.blankSession();
    const own = sd.activities || [];
    const prev = D.previousSessionOf(session);
    let carried = [];
    if (prev) {
      const psd = store[prev.id];
      if (psd && psd.activities) {
        const dismissed = new Set(sd.dismissedCarry || []);
        carried = psd.activities
          .filter(a => !a.done && !dismissed.has(a.id))
          .map(a => ({ ...a, _fromDate: prev.date, _fromId: prev.id }));
      }
    }
    const total = own.length;
    const done = own.filter(a => a.done).length;
    return { sd, own, carried, prev, total, done };
  }

  // Items d'un jour : séances + évènements, triés par horaire.
  function dayItems(dateStr, events) {
    const sessions = D.sessionsForDate(dateStr).map(s => ({ kind: "session", ...s }));
    const evs = (events || []).filter(e => e.date === dateStr)
      .map(e => ({ kind: "event", ...e }));
    return [...sessions, ...evs].sort((a, b) => D.minutesOf(a.start) - D.minutesOf(b.start));
  }

  // Compte de séances + évènements par jour (pour les pastilles de la bande semaine).
  function dayDots(dateStr, events) {
    const sessions = D.sessionsForDate(dateStr);
    const colors = sessions.map(s => D.classById(s.classId).color);
    const evs = (events || []).filter(e => e.date === dateStr).length;
    return { colors, events: evs };
  }

  window.LOGIC = { sessionView, dayItems, dayDots };
})();
