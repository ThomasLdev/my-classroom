/* App.jsx — shell, état global, actions, navigation, Tweaks. */
(function () {
  const { useState, useEffect } = React;
  const D = window.DATA, Icon = window.Icon, UI = window.UI;
  const CAL = window.CAL, SessionDetail = window.SessionDetail, BackOffice = window.BackOffice, EventEditor = window.EventEditor;
  const useMediaQuery = window.useMediaQuery;
  const { TweaksPanel, TweakSection, TweakRadio, useTweaks } = window;

  const LS_KEY = "gc_state_v3";
  const VARIANT_MAP = { "Cartes": "cards", "Frise": "timeline", "Ruban": "ribbon" };

  const TWEAK_DEFAULTS = /*EDITMODE-BEGIN*/{
    "palette": "doux",
    "dayView": "Cartes",
    "density": "Standard"
  }/*EDITMODE-END*/;

  function loadLS() {
    try { const s = localStorage.getItem(LS_KEY); return s ? JSON.parse(s) : null; } catch (e) { return null; }
  }
  function initialSelected() {
    let d = new Date(D.today);
    if (d.getDay() === 6) d = D.addDays(d, 2);
    if (d.getDay() === 0) d = D.addDays(d, 1);
    return d;
  }

  function App() {
    const [t, setTweak] = useTweaks(TWEAK_DEFAULTS);
    const saved = React.useRef(loadLS()).current;

    const [classes, setClasses] = useState(saved ? saved.classes : D.classes.map(c => ({ ...c })));
    const [timetable, setTimetable] = useState(saved ? saved.timetable : D.timetable.map(s => ({ ...s })));
    const [events, setEvents] = useState(saved ? saved.events : D.events.map(e => ({ ...e })));
    const [store, setStore] = useState(saved ? saved.store : D.seedStore);

    const [route, setRoute] = useState("calendar");        // calendar | session | backoffice
    const [openSess, setOpenSess] = useState(null);
    const [selected, setSelected] = useState(initialSelected());
    const [view, setView] = useState("week");              // desktop: day | week
    const [eventEdit, setEventEdit] = useState(undefined); // undefined closed, null new, obj edit
    const [profileOpen, setProfileOpen] = useState(false);

    const isDesktop = useMediaQuery("(min-width: 980px)");

    /* — synchronise les données vivantes pour les helpers DATA (avant rendu des enfants) — */
    D.classes.splice(0, D.classes.length, ...classes);
    D.timetable.splice(0, D.timetable.length, ...timetable);
    D.events.splice(0, D.events.length, ...events);

    /* — persistance — */
    useEffect(() => {
      try { localStorage.setItem(LS_KEY, JSON.stringify({ classes, timetable, events, store })); } catch (e) {}
    }, [classes, timetable, events, store]);

    /* — palette pastel (Tweaks) appliquée en variables CSS — */
    const pal = D.PALETTES[t.palette] || D.PALETTES.doux;
    const palVars = {};
    ["k1", "k2", "k3", "k4"].forEach(k => {
      palVars["--" + k + "-tint"] = pal[k].tint;
      palVars["--" + k + "-accent"] = pal[k].accent;
      palVars["--" + k + "-deep"] = pal[k].deep;
    });
    const variant = VARIANT_MAP[t.dayView] || "cards";

    /* — actions séance — */
    const upd = (sid, fn) => setStore(prev => {
      const next = { ...prev };
      const cur = { ...(next[sid] || D.blankSession()) };
      cur.activities = [...(cur.activities || [])];
      cur.docs = [...(cur.docs || [])];
      cur.dismissedCarry = [...(cur.dismissedCarry || [])];
      fn(cur);
      next[sid] = cur;
      return next;
    });
    const rid = (p) => p + Date.now().toString(36) + Math.random().toString(36).slice(2, 5);
    const actions = {
      toggleActivity: (sid, id) => upd(sid, c => { c.activities = c.activities.map(a => a.id === id ? { ...a, done: !a.done } : a); }),
      addActivity: (sid, a) => upd(sid, c => { c.activities = [...c.activities, { id: rid("a"), title: a.title, desc: a.desc, done: false }]; }),
      deleteActivity: (sid, id) => upd(sid, c => { c.activities = c.activities.filter(a => a.id !== id); }),
      setNote: (sid, text) => upd(sid, c => { c.note = text; }),
      addDoc: (sid, doc) => upd(sid, c => { c.docs = [...c.docs, doc]; }),
      deleteDoc: (sid, id) => upd(sid, c => { c.docs = c.docs.filter(d => d.id !== id); }),
      dismissCarry: (sid, id) => upd(sid, c => { if (!c.dismissedCarry.includes(id)) c.dismissedCarry = [...c.dismissedCarry, id]; }),
      // back office
      saveClass: (c) => setClasses(prev => prev.some(x => x.id === c.id) ? prev.map(x => x.id === c.id ? c : x) : [...prev, c]),
      deleteClass: (id) => { setClasses(prev => prev.filter(x => x.id !== id)); setTimetable(prev => prev.filter(s => s.classId !== id)); },
      saveSlot: (s) => setTimetable(prev => prev.some(x => x.id === s.id) ? prev.map(x => x.id === s.id ? s : x) : [...prev, s]),
      deleteSlot: (id) => setTimetable(prev => prev.filter(x => x.id !== id)),
      saveEvent: (e) => setEvents(prev => prev.some(x => x.id === e.id) ? prev.map(x => x.id === e.id ? e : x) : [...prev, e]),
      deleteEvent: (id) => setEvents(prev => prev.filter(x => x.id !== id))
    };

    function openSession(s) { setOpenSess(s); setRoute("session"); window.scrollTo && window.scrollTo(0, 0); }
    function backToCalendar() { setRoute("calendar"); setOpenSess(null); }
    function goToday() { setSelected(initialSelected()); }

    const navItems = [
      { id: "calendar", label: "Calendrier", icon: "calendar" },
      { id: "backoffice", label: "Organisation", icon: "clipboard" }
    ];
    function navTo(id) { setRoute(id); setOpenSess(null); }

    /* — écrans — */
    const CalendarScreen = () => {
      const monday = D.startOfWeek(selected);
      const friday = D.addDays(monday, 4);
      const showWeek = isDesktop && view === "week";
      return (
        <div className={"page " + (showWeek ? "view-week" : "view-day")}>
          <div style={{ display: "flex", alignItems: "center", gap: 12, marginBottom: showWeek ? 16 : 4, flexWrap: "wrap" }}>
            <div className="eyebrow">Mon agenda · Français</div>
            <span style={{ flex: 1 }} />
            <div className="viewswitch">
              <button className={view === "day" ? "is-active" : ""} onClick={() => setView("day")}>Jour</button>
              <button className={view === "week" ? "is-active" : ""} onClick={() => setView("week")}>Semaine</button>
            </div>
            <button className="btn btn--soft btn--sm" onClick={goToday}>Aujourd'hui</button>
            <button className="btn btn--sm" onClick={() => setEventEdit(null)}><Icon name="plus" /> Évènement</button>
          </div>

          {showWeek ? (
            <div className="weekgrid-wrap">
              <div className="dayhead" style={{ marginBottom: 14 }}>
                <div className="dayhead__date">
                  <div className="dayhead__dow" style={{ fontSize: 30 }}>
                    {monday.getDate()}–{friday.getDate()} {D.MONTHS[friday.getMonth()]}
                  </div>
                  <div className="dayhead__sub tnum">Semaine · {friday.getFullYear()}</div>
                </div>
                <div className="dayhead__nav">
                  <button className="iconbtn" onClick={() => setSelected(D.addDays(selected, -7))} aria-label="Semaine précédente"><Icon name="chevron-left" /></button>
                  <button className="iconbtn" onClick={() => setSelected(D.addDays(selected, 7))} aria-label="Semaine suivante"><Icon name="chevron-right" /></button>
                </div>
              </div>
              <CAL.WeekGrid selected={selected} store={store} events={events} onOpenSession={openSession} onOpenEvent={(e) => setEventEdit(e)} />
            </div>
          ) : (
            <div className="dayview-wrap">
              <CAL.DayHeader date={selected} onPrev={() => shiftDay(-1)} onNext={() => shiftDay(1)} />
              <CAL.WeekStrip selected={selected} events={events} onPick={setSelected} />
              <CAL.DayView selected={selected} setSelected={setSelected} store={store} events={events}
                variant={variant} onOpenSession={openSession} onOpenEvent={(e) => setEventEdit(e)} />
            </div>
          )}
        </div>
      );
    };
    // raccourci nav jour (réutilise la logique de direction interne via setSelected)
    function shiftDay(delta) { setSelected(D.addDays(selected, delta)); }

    return (
      <div className={"app density-" + t.density.toLowerCase()} style={palVars}>
        {/* Sidebar desktop */}
        <aside className="sidebar">
          <div className="sidebar__brand">
            <div className="mark">Gestion de classe</div>
            <div className="who">Mme Léaud<br />Lettres</div>
          </div>
          <nav className="sidebar__nav">
            <button className="btn" style={{ width: "100%", justifyContent: "flex-start", marginBottom: 10 }} onClick={() => setEventEdit(null)}><Icon name="plus" /> Nouvel évènement</button>
            {navItems.map(n => (
              <button key={n.id} className={"side-link" + ((route === n.id || (n.id === "calendar" && route === "session")) ? " is-active" : "")} onClick={() => navTo(n.id)}>
                <Icon name={n.icon} /> {n.label}
              </button>
            ))}
          </nav>
          <div className="sidebar__foot">
            <div className="sidebar__me">
              <span className="av">CL</span>
              <span className="nm">Camille Léaud<small>Collège Jean Moulin</small></span>
            </div>
          </div>
        </aside>

        {/* Topbar mobile */}
        <header className="topbar">
          <div className="topbar__title">
            <span className="brand">Gestion de classe</span>
            <span className="who">{route === "backoffice" ? "Organisation" : route === "session" ? "Séance" : "Mon agenda"}</span>
          </div>
          <span className="topbar__spacer" />
          {route !== "session" && route !== "backoffice" && (
            <button className="iconbtn" onClick={() => setEventEdit(null)} aria-label="Nouvel évènement"><Icon name="plus" /></button>
          )}
        </header>

        {/* Main */}
        <main className="app__main">
          <div className="scroll" key={route + (openSess ? openSess.id : "")}>
            {route === "session" && openSess
              ? <div className="page"><SessionDetail session={openSess} store={store} actions={actions} onBack={backToCalendar} /></div>
              : route === "backoffice"
                ? <BackOffice classes={classes} timetable={timetable} events={events} actions={actions} />
                : <CalendarScreen />}
          </div>
        </main>

        {/* Bottom nav mobile — style Pinterest */}
        <nav className="bottomnav">
          <button className={"navbtn" + ((route === "calendar" || route === "session") ? " is-active" : "")} onClick={() => navTo("calendar")} aria-label="Calendrier">
            <Icon name="calendar" /><span className="navbtn__dot" />
          </button>
          <button className="navbtn" onClick={() => { navTo("calendar"); goToday(); }} aria-label="Aujourd'hui">
            <Icon name="calendar-clock" /><span className="navbtn__dot" />
          </button>
          <button className="navbtn navbtn--create" onClick={() => setEventEdit(null)} aria-label="Nouvel évènement">
            <span className="create-pill"><Icon name="plus" /></span>
          </button>
          <button className={"navbtn" + (route === "backoffice" ? " is-active" : "")} onClick={() => navTo("backoffice")} aria-label="Organisation">
            <Icon name="clipboard" /><span className="navbtn__dot" />
          </button>
          <button className={"navbtn navbtn--avatar" + (profileOpen ? " is-active" : "")} onClick={() => setProfileOpen(true)} aria-label="Profil">
            <span className="navbtn__av">CL</span><span className="navbtn__dot" />
          </button>
        </nav>

        {/* Profil (depuis l'onglet avatar) */}
        {profileOpen && (
          <UI.Sheet title="Mon profil" onClose={() => setProfileOpen(false)}>
            <div style={{ display: "flex", alignItems: "center", gap: 14, marginBottom: 18 }}>
              <span style={{ width: 56, height: 56, borderRadius: "50%", background: "var(--k3-tint)", color: "var(--k3-deep)", display: "grid", placeItems: "center", fontWeight: 700, fontSize: 20 }}>CL</span>
              <div>
                <div style={{ fontWeight: 700, fontSize: 17 }}>Camille Léaud</div>
                <div style={{ color: "var(--ink-2)", fontSize: 14 }}>Professeure de Lettres · Collège Jean Moulin</div>
              </div>
            </div>
            <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: 10 }}>
              <div style={{ background: "var(--surface-2)", borderRadius: "var(--r-md)", padding: "13px 14px" }}>
                <div style={{ fontFamily: "var(--serif)", fontSize: 26, lineHeight: 1 }}>{classes.length}</div>
                <div style={{ color: "var(--ink-2)", fontSize: 13, marginTop: 4 }}>classes</div>
              </div>
              <div style={{ background: "var(--surface-2)", borderRadius: "var(--r-md)", padding: "13px 14px" }}>
                <div style={{ fontFamily: "var(--serif)", fontSize: 26, lineHeight: 1 }}>{timetable.length}h</div>
                <div style={{ color: "var(--ink-2)", fontSize: 13, marginTop: 4 }}>par semaine</div>
              </div>
            </div>
            <div className="sheet__actions" style={{ marginTop: 16 }}>
              <button className="btn btn--ghost" onClick={() => { setProfileOpen(false); navTo("backoffice"); }}><Icon name="clipboard" /> Organisation de l'année</button>
            </div>
          </UI.Sheet>
        )}

        {/* Évènement (vue/édition rapide depuis le calendrier) */}
        {eventEdit !== undefined && EventEditor && (
          <EventEditor initial={eventEdit} onClose={() => setEventEdit(undefined)} onSave={actions.saveEvent} onDelete={actions.deleteEvent} />
        )}

        {/* Tweaks */}
        <TweaksPanel title="Réglages">
          <TweakSection label="Palette pastel" />
          <div className="pal-grid">
            {Object.keys(D.PALETTES).map(key => {
              const p = D.PALETTES[key];
              return (
                <button key={key} className={"pal-opt" + (t.palette === key ? " is-on" : "")} onClick={() => setTweak("palette", key)}>
                  <span className="pal-opt__row">
                    {["k1", "k2", "k3", "k4"].map(k => <i key={k} style={{ background: p[k].tint, border: "1px solid " + p[k].accent }} />)}
                  </span>
                  <span className="pal-opt__lab">{p.label}</span>
                </button>
              );
            })}
          </div>
          <div style={{ height: 14 }} />
          <TweakRadio label="Vue du jour" value={t.dayView} options={["Cartes", "Frise", "Ruban"]} onChange={v => setTweak("dayView", v)} />
          <TweakRadio label="Densité" value={t.density} options={["Compact", "Standard", "Confort"]} onChange={v => setTweak("density", v)} />
        </TweaksPanel>
      </div>
    );
  }

  ReactDOM.createRoot(document.getElementById("root")).render(<App />);
})();
