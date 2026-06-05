/* ============================================================
   Gestion de classe — données & logique métier
   Plain JS (no Babel). Tout est exposé sur window.DATA.
   Modèle :
   - classes : les classes dont le prof a la charge
   - timetable : emploi du temps annuel = créneaux récurrents (par jour de semaine)
   - sessions : instances concrètes d'un créneau à une date (calculées)
   - sessionStore : données saisies par séance (activités, notes, documents)
   - events : évènements génériques (titre, description, horaire, date)
   ============================================================ */
(function () {
  "use strict";

  /* ---------- Palettes pastel (réglables en Tweaks) ----------
     4 clés de couleur (k1..k4), une par classe. tint = aplat, accent = trait/point. */
  const PALETTES = {
    doux: {
      label: "Doux",
      k1: { tint: "#E6EEF7", accent: "#577FA8", deep: "#3C5C7E" },
      k2: { tint: "#E7EFE4", accent: "#6E9A66", deep: "#4F7349" },
      k3: { tint: "#F8E9D9", accent: "#C28A54", deep: "#996A39" },
      k4: { tint: "#EDE5F3", accent: "#8A72B4", deep: "#67518F" }
    },
    froid: {
      label: "Froid",
      k1: { tint: "#E4EDF5", accent: "#577EA6", deep: "#3C5b7c" },
      k2: { tint: "#DFEEEA", accent: "#549389", deep: "#386b63" },
      k3: { tint: "#E8EAF7", accent: "#7681C2", deep: "#535d97" },
      k4: { tint: "#EDE4F2", accent: "#8E73B0", deep: "#675089" }
    },
    chaud: {
      label: "Chaud",
      k1: { tint: "#F7EAD9", accent: "#C08A53", deep: "#946739" },
      k2: { tint: "#F4E8E2", accent: "#BF8579", deep: "#915d52" },
      k3: { tint: "#F0EBDA", accent: "#A0974F", deep: "#766d36" },
      k4: { tint: "#F2E3E7", accent: "#B57E96", deep: "#8a596f" }
    }
  };

  /* ---------- Classes ---------- */
  const classes = [
    { id: "6a", name: "6e A", level: "6e", color: "k1", room: "214", students: 27 },
    { id: "6b", name: "6e B", level: "6e", color: "k2", room: "214", students: 28 },
    { id: "5a", name: "5e A", level: "5e", color: "k3", room: "117", students: 25 },
    { id: "5b", name: "5e B", level: "5e", color: "k4", room: "117", students: 26 }
  ];

  /* ---------- Emploi du temps annuel (créneaux récurrents) ----------
     day : 1=Lundi … 5=Vendredi · start/end "HH:MM" */
  const timetable = [
    { id: "s1",  day: 1, start: "08:00", end: "08:55", classId: "6a" },
    { id: "s2",  day: 1, start: "09:00", end: "09:55", classId: "5a" },
    { id: "s3",  day: 1, start: "11:00", end: "11:55", classId: "6b" },
    { id: "s4",  day: 1, start: "14:00", end: "14:55", classId: "5b" },

    { id: "s5",  day: 2, start: "08:00", end: "08:55", classId: "6b" },
    { id: "s6",  day: 2, start: "10:00", end: "10:55", classId: "5a" },
    { id: "s7",  day: 2, start: "11:00", end: "11:55", classId: "6a" },

    { id: "s8",  day: 3, start: "08:00", end: "08:55", classId: "5b" },
    { id: "s9",  day: 3, start: "09:00", end: "09:55", classId: "6a" },

    { id: "s10", day: 4, start: "09:00", end: "09:55", classId: "6b" },
    { id: "s11", day: 4, start: "10:00", end: "10:55", classId: "5b" },
    { id: "s12", day: 4, start: "14:00", end: "14:55", classId: "5a" },
    { id: "s13", day: 4, start: "15:00", end: "15:55", classId: "6a" },

    { id: "s14", day: 5, start: "08:00", end: "08:55", classId: "6a" },
    { id: "s15", day: 5, start: "09:00", end: "09:55", classId: "6b" },
    { id: "s16", day: 5, start: "10:00", end: "10:55", classId: "5a" },
    { id: "s17", day: 5, start: "11:00", end: "11:55", classId: "5b" }
  ];

  /* ---------- Helpers dates ---------- */
  const DOW = ["Dimanche", "Lundi", "Mardi", "Mercredi", "Jeudi", "Vendredi", "Samedi"];
  const DOW_SHORT = ["Dim", "Lun", "Mar", "Mer", "Jeu", "Ven", "Sam"];
  const MONTHS = ["janvier", "février", "mars", "avril", "mai", "juin", "juillet", "août", "septembre", "octobre", "novembre", "décembre"];

  function ymd(d) {
    const m = String(d.getMonth() + 1).padStart(2, "0");
    const day = String(d.getDate()).padStart(2, "0");
    return d.getFullYear() + "-" + m + "-" + day;
  }
  function parseYmd(s) { const [y, m, d] = s.split("-").map(Number); return new Date(y, m - 1, d); }
  function addDays(d, n) { const x = new Date(d); x.setDate(x.getDate() + n); return x; }
  function startOfWeek(d) { // Monday
    const x = new Date(d); const wd = (x.getDay() + 6) % 7; x.setDate(x.getDate() - wd); x.setHours(0,0,0,0); return x;
  }
  function isSameDay(a, b) { return ymd(a) === ymd(b); }
  function minutesOf(hhmm) { const [h, m] = hhmm.split(":").map(Number); return h * 60 + m; }
  function fmtLongDate(d) { return DOW[d.getDay()] + " " + d.getDate() + " " + MONTHS[d.getMonth()]; }

  /* ---------- Génération des sessions sur une fenêtre de semaines ----------
     Une session = { id, slotId, classId, date(ymd), start, end, day } */
  function sessionId(slotId, dateStr) { return slotId + "@" + dateStr; }

  function sessionsForDate(dateStr) {
    const d = parseYmd(dateStr);
    const dow = d.getDay(); // 0..6
    return timetable
      .filter(s => s.day === dow)
      .map(s => ({ id: sessionId(s.id, dateStr), slotId: s.id, classId: s.classId, date: dateStr, start: s.start, end: s.end, day: s.day }))
      .sort((a, b) => minutesOf(a.start) - minutesOf(b.start));
  }

  // Toutes les occurrences d'une classe, triées par date+heure, sur une fenêtre large.
  function classOccurrences(classId, fromDate, weeksBack, weeksFwd) {
    const monday = startOfWeek(fromDate);
    const out = [];
    for (let w = -weeksBack; w <= weeksFwd; w++) {
      for (let dd = 0; dd < 5; dd++) {
        const date = addDays(monday, w * 7 + dd);
        sessionsForDate(ymd(date)).forEach(s => { if (s.classId === classId) out.push(s); });
      }
    }
    return out.sort((a, b) => (a.date + a.start).localeCompare(b.date + b.start));
  }

  // Séance précédente de la même classe, strictement avant la séance donnée.
  function previousSessionOf(session) {
    const occ = classOccurrences(session.classId, parseYmd(session.date), 4, 1);
    const idx = occ.findIndex(s => s.id === session.id);
    if (idx > 0) return occ[idx - 1];
    return null;
  }

  /* ---------- Données par séance (activités / notes / documents) ----------
     sessionStore[sessionId] = { activities:[{id,title,desc,done}], note:"", docs:[{id,name,kind}], dismissedCarry:[activityId] } */
  function blankSession() { return { activities: [], note: "", docs: [], dismissedCarry: [] }; }

  // Seed : on remplit la semaine courante + la précédente pour rendre le report visible.
  function buildSeed() {
    const store = {};
    const today = new Date(); today.setHours(0,0,0,0);
    const monday = startOfWeek(today);
    const get = (sid) => (store[sid] = store[sid] || blankSession());

    // Pour chaque classe : poser un plan sur l'avant-dernière occurrence (avec un item non terminé → report)
    classes.forEach(c => {
      const occ = classOccurrences(c.id, today, 2, 2);
      const past = occ.filter(s => parseYmd(s.date) < today);
      if (past.length >= 1) {
        const prev = past[past.length - 1]; // séance la plus récente passée
        const a = get(prev.id);
        a.activities = SEED_PLANS[c.id] ? SEED_PLANS[c.id].map((p, i) => ({
          id: prev.id + "-a" + i, title: p.title, desc: p.desc, done: p.done
        })) : [];
      }
    });
    return { store, monday, today };
  }

  // Petits plans d'exemple par classe (prof de français)
  const SEED_PLANS = {
    "6a": [
      { title: "Dictée préparée — les homophones", desc: "a/à, et/est. Correction collective au tableau.", done: true },
      { title: "Lecture : Le Roman de Renart, extrait 3", desc: "Lecture à voix haute, repérage du registre comique.", done: true },
      { title: "Rédaction : portrait d'un personnage rusé", desc: "Brouillon à terminer — 10 lignes minimum.", done: false }
    ],
    "6b": [
      { title: "Conjugaison : présent de l'indicatif (3e groupe)", desc: "Exercices 4 et 5 p.112.", done: true },
      { title: "Vocabulaire : le champ lexical de la forêt", desc: "Carte mentale en binôme.", done: false }
    ],
    "5a": [
      { title: "Étude de la langue : compléments circonstanciels", desc: "Repérage dans le texte distribué.", done: true },
      { title: "Chevalerie : lecture de l'extrait de Chrétien de Troyes", desc: "Questions 1 à 4.", done: false }
    ],
    "5b": [
      { title: "Expression écrite : récit d'aventure", desc: "Plan en trois étapes — relecture des critères.", done: true },
      { title: "Orthographe : accord du participe passé", desc: "Bilan à finir.", done: false }
    ]
  };

  /* ---------- Évènements génériques (titre, description, horaire, date) ---------- */
  function buildEvents(monday) {
    const ymdOf = (offset) => ymd(addDays(monday, offset));
    return [
      { id: "e1", title: "Réunion équipe pédagogique", desc: "Salle des profs. Préparation du conseil de classe.", date: ymdOf(3), start: "17:00", end: "18:00" },
      { id: "e2", title: "Saisie des notes — Trimestre 3", desc: "Date limite de remontée des moyennes sur Pronote.", date: ymdOf(4), start: "23:59", end: "" },
      { id: "e3", title: "Conseil de classe 6e A", desc: "CPE + équipe. Salle 12.", date: ymdOf(8), start: "16:30", end: "18:00" },
      { id: "e4", title: "Sortie théâtre — 5e A", desc: "Représentation de Molière. RDV 13h30 à l'entrée.", date: ymdOf(9), start: "14:00", end: "17:00" }
    ];
  }

  const seed = buildSeed();

  window.DATA = {
    PALETTES,
    classes,
    timetable,
    DOW, DOW_SHORT, MONTHS,
    ymd, parseYmd, addDays, startOfWeek, isSameDay, minutesOf, fmtLongDate, sessionId,
    sessionsForDate, classOccurrences, previousSessionOf, blankSession,
    seedStore: seed.store,
    events: buildEvents(seed.monday),
    today: seed.today,
    classById: (id) => classes.find(c => c.id === id)
  };
})();
