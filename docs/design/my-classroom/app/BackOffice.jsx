/* BackOffice.jsx — déclaration de l'année : classes, emploi du temps, évènements. */
(function () {
  const { useState } = React;
  const D = window.DATA, Icon = window.Icon, UI = window.UI;
  const { tintOf, accentOf, deepOf } = UI;
  const Sheet = UI.Sheet, Field = UI.Field;

  const COLOR_KEYS = ["k1", "k2", "k3", "k4"];
  const PERIODS = ["08:00", "09:00", "10:00", "11:00", "14:00", "15:00", "16:00"];
  function addMin(hhmm, m) {
    const t = D.minutesOf(hhmm) + m;
    return String(Math.floor(t / 60)).padStart(2, "0") + ":" + String(t % 60).padStart(2, "0");
  }

  /* ---------- éditeur de classe ---------- */
  function ClassEditor({ initial, onSave, onDelete, onClose }) {
    const [name, setName] = useState(initial ? initial.name : "");
    const [level, setLevel] = useState(initial ? initial.level : "6e");
    const [color, setColor] = useState(initial ? initial.color : "k1");
    const [room, setRoom] = useState(initial ? initial.room : "");
    const [students, setStudents] = useState(initial ? initial.students : 26);
    return (
      <Sheet title={initial ? "Modifier la classe" : "Nouvelle classe"} subtitle="Une teinte pastel par classe, reprise partout dans le calendrier." onClose={onClose}>
        <Field label="Nom de la classe"><input className="input" placeholder="6e A" value={name} onChange={e => setName(e.target.value)} /></Field>
        <div className="field-row">
          <Field label="Niveau">
            <select className="select" value={level} onChange={e => setLevel(e.target.value)}>
              <option>6e</option><option>5e</option><option>4e</option><option>3e</option>
            </select>
          </Field>
          <Field label="Salle"><input className="input" placeholder="214" value={room} onChange={e => setRoom(e.target.value)} /></Field>
          <Field label="Effectif"><input className="input tnum" type="number" value={students} onChange={e => setStudents(+e.target.value)} /></Field>
        </div>
        <Field label="Couleur">
          <div style={{ display: "flex", gap: 10 }}>
            {COLOR_KEYS.map(k => (
              <button key={k} onClick={() => setColor(k)} aria-label={k}
                style={{ width: 44, height: 44, borderRadius: 13, background: tintOf(k), border: color === k ? "2px solid var(--ink)" : "1px solid var(--hairline-2)", position: "relative" }}>
                <span style={{ position: "absolute", left: 8, top: 8, width: 11, height: 11, borderRadius: "50%", background: accentOf(k) }} />
              </button>
            ))}
          </div>
        </Field>
        <div className="sheet__actions">
          {initial && <button className="btn btn--ghost" onClick={() => { onDelete(initial.id); onClose(); }} style={{ flex: "0 0 auto", color: "var(--status-error)", borderColor: "var(--hairline-2)" }}><Icon name="trash" /></button>}
          <button className="btn" onClick={() => { if (name.trim()) { onSave({ id: initial ? initial.id : "c" + Date.now(), name: name.trim(), level, color, room: room.trim(), students: +students || 0 }); onClose(); } }}>
            {initial ? "Enregistrer" : "Ajouter la classe"}
          </button>
        </div>
      </Sheet>
    );
  }

  /* ---------- éditeur de créneau (emploi du temps) ---------- */
  function SlotEditor({ classes, slot, day, start, onSave, onDelete, onClose }) {
    const [classId, setClassId] = useState(slot ? slot.classId : (classes[0] && classes[0].id));
    const [end, setEnd] = useState(slot ? slot.end : addMin(start, 55));
    return (
      <Sheet title={slot ? "Modifier le créneau" : "Ajouter un cours"} subtitle={`${D.DOW[day]} · ${start}`} onClose={onClose}>
        <Field label="Classe">
          <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: 8 }}>
            {classes.map(c => (
              <button key={c.id} onClick={() => setClassId(c.id)}
                style={{ display: "flex", alignItems: "center", gap: 8, padding: "11px 12px", borderRadius: 12, textAlign: "left",
                  background: classId === c.id ? tintOf(c.color) : "var(--surface)", border: classId === c.id ? "2px solid " + accentOf(c.color) : "1px solid var(--hairline-2)", fontWeight: 600, color: classId === c.id ? deepOf(c.color) : "var(--ink)" }}>
                <i style={{ width: 9, height: 9, borderRadius: "50%", background: accentOf(c.color) }} /> {c.name}
              </button>
            ))}
          </div>
        </Field>
        <div className="field-row">
          <Field label="Début"><input className="input tnum" value={start} disabled style={{ color: "var(--ink-3)" }} /></Field>
          <Field label="Fin"><input className="input tnum" value={end} onChange={e => setEnd(e.target.value)} /></Field>
        </div>
        <div className="sheet__actions">
          {slot && <button className="btn btn--ghost" onClick={() => { onDelete(slot.id); onClose(); }} style={{ flex: "0 0 auto", color: "var(--status-error)", borderColor: "var(--hairline-2)" }}><Icon name="trash" /></button>}
          <button className="btn" onClick={() => { onSave({ id: slot ? slot.id : "s" + Date.now(), day, start, end, classId }); onClose(); }}>{slot ? "Enregistrer" : "Ajouter"}</button>
        </div>
      </Sheet>
    );
  }

  /* ---------- éditeur d'évènement ---------- */
  function EventEditor({ initial, onSave, onDelete, onClose }) {
    const [title, setTitle] = useState(initial ? initial.title : "");
    const [desc, setDesc] = useState(initial ? initial.desc : "");
    const [date, setDate] = useState(initial ? initial.date : D.ymd(D.today));
    const [start, setStart] = useState(initial ? initial.start : "17:00");
    const [end, setEnd] = useState(initial ? initial.end : "");
    return (
      <Sheet title={initial ? "Modifier l'évènement" : "Nouvel évènement"} subtitle="Réunion, deadline, sortie, rappel — tout type." onClose={onClose}>
        <Field label="Titre"><input className="input" placeholder="Réunion équipe pédagogique" value={title} onChange={e => setTitle(e.target.value)} /></Field>
        <Field label="Description"><textarea className="textarea" placeholder="Lieu, ordre du jour, détails…" value={desc} onChange={e => setDesc(e.target.value)} /></Field>
        <Field label="Date"><input className="input" type="date" value={date} onChange={e => setDate(e.target.value)} /></Field>
        <div className="field-row">
          <Field label="Heure"><input className="input tnum" value={start} onChange={e => setStart(e.target.value)} /></Field>
          <Field label="Fin (facultatif)"><input className="input tnum" placeholder="—" value={end} onChange={e => setEnd(e.target.value)} /></Field>
        </div>
        <div className="sheet__actions">
          {initial && <button className="btn btn--ghost" onClick={() => { onDelete(initial.id); onClose(); }} style={{ flex: "0 0 auto", color: "var(--status-error)", borderColor: "var(--hairline-2)" }}><Icon name="trash" /></button>}
          <button className="btn" onClick={() => { if (title.trim()) { onSave({ id: initial ? initial.id : "e" + Date.now(), title: title.trim(), desc: desc.trim(), date, start, end }); onClose(); } }}>{initial ? "Enregistrer" : "Ajouter"}</button>
        </div>
      </Sheet>
    );
  }

  /* ---------- page back office ---------- */
  function BackOffice({ classes, timetable, events, actions }) {
    const [tab, setTab] = useState("classes");
    const [editClass, setEditClass] = useState(undefined); // undefined=closed, null=new, obj=edit
    const [editSlot, setEditSlot] = useState(null);        // {slot?, day, start}
    const [editEvent, setEditEvent] = useState(undefined);

    return (
      <div className="page">
        <div className="bo-head">
          <h2>Organisation de l'année</h2>
          <p>Déclarez vos classes et votre emploi du temps. Le calendrier remplit alors chaque semaine avec des séances prêtes à planifier.</p>
        </div>

        <div className="tabs">
          <button className={tab === "classes" ? "is-active" : ""} onClick={() => setTab("classes")}>Mes classes</button>
          <button className={tab === "edt" ? "is-active" : ""} onClick={() => setTab("edt")}>Emploi du temps</button>
          <button className={tab === "events" ? "is-active" : ""} onClick={() => setTab("events")}>Évènements</button>
        </div>

        {tab === "classes" && (
          <>
            <div className="class-grid">
              {classes.map(c => (
                <div className="class-card" key={c.id}>
                  <div className="class-card__swatch" style={{ background: tintOf(c.color) }}><i style={{ background: accentOf(c.color) }} /></div>
                  <div className="class-card__body">
                    <div className="class-card__name">{c.name}</div>
                    <div className="class-card__meta">Salle {c.room || "—"} · {c.students} élèves</div>
                  </div>
                  <button className="iconbtn iconbtn--bare class-card__edit" onClick={() => setEditClass(c)} aria-label="Modifier"><Icon name="pencil" /></button>
                </div>
              ))}
            </div>
            <button className="add-trigger" style={{ marginTop: 14 }} onClick={() => setEditClass(null)}><Icon name="plus" /> Ajouter une classe</button>
          </>
        )}

        {tab === "edt" && (
          <div className="tt-scroll">
            <div className="tt">
              <div className="tt__corner" />
              {[1, 2, 3, 4, 5].map(d => <div className="tt__colhead" key={d}>{D.DOW[d]}</div>)}
              {PERIODS.map(p => (
                <React.Fragment key={p}>
                  <div className="tt__time tnum">{p}</div>
                  {[1, 2, 3, 4, 5].map(d => {
                    const slot = timetable.find(s => s.day === d && s.start === p);
                    if (slot) {
                      const c = D.classById(slot.classId);
                      return (
                        <div className="tt__cell" key={d}>
                          <button className="tt__slot" style={{ background: tintOf(c.color), color: deepOf(c.color) }} onClick={() => setEditSlot({ slot, day: d, start: p })}>
                            {c.name}<small>{slot.start}–{slot.end}</small>
                            <span className="x"><Icon name="x" size={13} /></span>
                          </button>
                        </div>
                      );
                    }
                    return <div className="tt__cell is-empty" key={d} onClick={() => setEditSlot({ slot: null, day: d, start: p })}><Icon name="plus" size={16} style={{ color: "var(--ink-3)" }} /></div>;
                  })}
                </React.Fragment>
              ))}
            </div>
            <p style={{ color: "var(--ink-3)", fontSize: 13, marginTop: 14 }}>Touchez une case vide pour ajouter un cours, ou un cours existant pour le modifier.</p>
          </div>
        )}

        {tab === "events" && (
          <>
            <div className="class-grid" style={{ gridTemplateColumns: "1fr" }}>
              {[...events].sort((a, b) => (a.date + a.start).localeCompare(b.date + b.start)).map(e => {
                const d = D.parseYmd(e.date);
                return (
                  <div className="class-card" key={e.id}>
                    <div className="class-card__swatch" style={{ background: "var(--event-tint)", display: "grid", placeItems: "center" }}><Icon name="bell" size={20} style={{ color: "var(--event-accent)" }} /></div>
                    <div className="class-card__body">
                      <div className="class-card__name">{e.title}</div>
                      <div className="class-card__meta">{D.DOW_SHORT[d.getDay()]} {d.getDate()} {D.MONTHS[d.getMonth()]} · {e.start}{e.end ? "–" + e.end : ""}</div>
                    </div>
                    <button className="iconbtn iconbtn--bare class-card__edit" onClick={() => setEditEvent(e)} aria-label="Modifier"><Icon name="pencil" /></button>
                  </div>
                );
              })}
            </div>
            <button className="add-trigger" style={{ marginTop: 14 }} onClick={() => setEditEvent(null)}><Icon name="plus" /> Ajouter un évènement</button>
          </>
        )}

        {editClass !== undefined && (
          <ClassEditor initial={editClass} onClose={() => setEditClass(undefined)}
            onSave={actions.saveClass} onDelete={actions.deleteClass} />
        )}
        {editSlot && (
          <SlotEditor classes={classes} slot={editSlot.slot} day={editSlot.day} start={editSlot.start}
            onClose={() => setEditSlot(null)} onSave={actions.saveSlot} onDelete={actions.deleteSlot} />
        )}
        {editEvent !== undefined && (
          <EventEditor initial={editEvent} onClose={() => setEditEvent(undefined)}
            onSave={actions.saveEvent} onDelete={actions.deleteEvent} />
        )}
      </div>
    );
  }

  window.BackOffice = BackOffice;
  window.EventEditor = EventEditor;
})();
