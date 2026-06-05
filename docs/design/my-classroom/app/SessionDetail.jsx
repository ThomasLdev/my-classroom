/* SessionDetail.jsx — détail d'une séance : activités, report, notes, documents. */
(function () {
  const { useState, useRef } = React;
  const D = window.DATA, L = window.LOGIC, Icon = window.Icon;
  const { tintOf, accentOf, deepOf, timeRange } = window.UI;

  function docIcon(name) {
    const ext = (name.split(".").pop() || "").toLowerCase();
    if (["png", "jpg", "jpeg", "gif", "webp", "heic"].includes(ext)) return { name: "image", tint: "var(--k1-tint)", color: "var(--k1-deep)" };
    if (["pdf"].includes(ext)) return { name: "file", tint: "var(--k3-tint)", color: "var(--k3-deep)" };
    if (["doc", "docx", "odt", "txt"].includes(ext)) return { name: "file", tint: "var(--k4-tint)", color: "var(--k4-deep)" };
    return { name: "paperclip", tint: "var(--surface-2)", color: "var(--ink-2)" };
  }
  function prettySize(b) {
    if (!b) return "";
    if (b < 1024) return b + " o";
    if (b < 1048576) return (b / 1024).toFixed(0) + " Ko";
    return (b / 1048576).toFixed(1) + " Mo";
  }

  function AddActivity({ onAdd }) {
    const [open, setOpen] = useState(false);
    const [title, setTitle] = useState("");
    const [desc, setDesc] = useState("");
    const titleRef = useRef(null);
    function submit() {
      if (!title.trim()) return;
      onAdd({ title: title.trim(), desc: desc.trim() });
      setTitle(""); setDesc(""); setOpen(false);
    }
    if (!open) {
      return (
        <button className="add-trigger" onClick={() => { setOpen(true); setTimeout(() => titleRef.current && titleRef.current.focus(), 30); }}>
          <Icon name="plus" /> Ajouter une activité
        </button>
      );
    }
    return (
      <div className="act" style={{ flexDirection: "column", alignItems: "stretch", gap: 10, borderColor: "var(--clay)" }}>
        <input ref={titleRef} className="input" placeholder="Titre de l'activité" value={title}
          onChange={e => setTitle(e.target.value)} onKeyDown={e => { if (e.key === "Enter") submit(); }} />
        <textarea className="textarea" placeholder="Consigne / description (facultatif)" value={desc}
          onChange={e => setDesc(e.target.value)} style={{ minHeight: 60 }} />
        <div style={{ display: "flex", gap: 8 }}>
          <button className="btn btn--sm" onClick={submit}>Ajouter</button>
          <button className="btn btn--ghost btn--sm" onClick={() => { setOpen(false); setTitle(""); setDesc(""); }}>Annuler</button>
        </div>
      </div>
    );
  }

  function SessionDetail({ session, store, actions, onBack }) {
    const cls = D.classById(session.classId);
    const vm = L.sessionView(session, store);
    const date = D.parseYmd(session.date);
    const dropRef = useRef(null);
    const [over, setOver] = useState(false);
    const fileRef = useRef(null);

    function handleFiles(fileList) {
      Array.from(fileList).forEach(f => {
        actions.addDoc(session.id, { id: "d" + Date.now() + Math.random().toString(36).slice(2, 6), name: f.name, size: f.size });
      });
    }

    return (
      <div className="detail">
        <div className="detail__hero">
          <button className="detail__back" onClick={onBack}><Icon name="arrow-left" /> Retour au calendrier</button>
          <div className="detail__title">
            <span className="detail__cls" style={{ color: deepOf(cls.color) }}>{cls.name}</span>
            <span className="classchip" style={{ background: tintOf(cls.color), color: deepOf(cls.color) }}>
              <i style={{ background: accentOf(cls.color) }} /> Français
            </span>
          </div>
          <div className="detail__when">
            <span><Icon name="calendar" /> {D.fmtLongDate(date)}</span>
            <span><Icon name="clock" /> {timeRange(session.start, session.end)}</span>
            <span><Icon name="pin" /> Salle {cls.room}</span>
            <span><Icon name="users" /> {cls.students} élèves</span>
          </div>
        </div>

        {/* Report */}
        {vm.carried.length > 0 && (
          <div className="carry-block">
            <div className="carry-block__head">
              <Icon name="rotate" /> Reporté de la séance du {D.parseYmd(vm.prev.date).getDate()} {D.MONTHS[D.parseYmd(vm.prev.date).getMonth()]}
            </div>
            {vm.carried.map(a => (
              <div className="carry-item" key={a.id}>
                <button className="act__check" onClick={() => actions.toggleActivity(vm.prev.id, a.id)} aria-label="Marquer terminée">
                  <Icon name="check" strokeWidth={2.4} />
                </button>
                <div className="carry-item__body">
                  <div className="carry-item__title">{a.title}</div>
                  {a.desc && <div className="carry-item__desc">{a.desc}</div>}
                </div>
                <button className="carry-item__dismiss" onClick={() => actions.dismissCarry(session.id, a.id)}>Ne pas reporter</button>
              </div>
            ))}
          </div>
        )}

        {/* Activités */}
        <div className="section">
          <div className="section__head">
            <h3>Activités</h3>
            {vm.total > 0 && <span className="count tnum">{vm.done}/{vm.total} terminées</span>}
          </div>
          <div className="act-list">
            {vm.own.map(a => (
              <div className={"act" + (a.done ? " is-done" : "")} key={a.id}>
                <button className="act__check" onClick={() => actions.toggleActivity(session.id, a.id)} aria-label="Terminée">
                  <Icon name="check" strokeWidth={2.4} />
                </button>
                <div className="act__body">
                  <div className="act__title">{a.title}</div>
                  {a.desc && <div className="act__desc">{a.desc}</div>}
                </div>
                <button className="act__del" onClick={() => actions.deleteActivity(session.id, a.id)} aria-label="Supprimer"><Icon name="trash" /></button>
              </div>
            ))}
          </div>
          <AddActivity onAdd={(a) => actions.addActivity(session.id, a)} />
        </div>

        {/* Notes */}
        <div className="section">
          <div className="section__head"><h3>Notes de séance</h3></div>
          <textarea className="notearea" placeholder="Ce qui a marché, les élèves à revoir, les points à reprendre la prochaine fois…"
            value={vm.sd.note || ""} onChange={e => actions.setNote(session.id, e.target.value)} />
          {(vm.sd.note || "").length > 0 && <div className="note-saved"><Icon name="check-circle" /> Enregistré automatiquement</div>}
        </div>

        {/* Documents */}
        <div className="section">
          <div className="section__head">
            <h3>Documents</h3>
            {(vm.sd.docs || []).length > 0 && <span className="count tnum">{vm.sd.docs.length}</span>}
            <span className="grow" />
            <button className="btn btn--soft btn--sm" onClick={() => fileRef.current && fileRef.current.click()}><Icon name="upload" /> Joindre</button>
            <input ref={fileRef} type="file" multiple style={{ display: "none" }} onChange={e => { handleFiles(e.target.files); e.target.value = ""; }} />
          </div>
          {(vm.sd.docs || []).length > 0 && (
            <div className="doc-grid" style={{ marginBottom: 10 }}>
              {vm.sd.docs.map(d => {
                const ic = docIcon(d.name);
                return (
                  <div className="doc" key={d.id}>
                    <span className="doc__ic" style={{ background: ic.tint, color: ic.color }}><Icon name={ic.name} /></span>
                    <div className="doc__body">
                      <div className="doc__name">{d.name}</div>
                      <div className="doc__meta">{prettySize(d.size) || "Pièce jointe"}</div>
                    </div>
                    <button className="doc__del" onClick={() => actions.deleteDoc(session.id, d.id)} aria-label="Retirer"><Icon name="x" /></button>
                  </div>
                );
              })}
            </div>
          )}
          <div ref={dropRef} className={"dropzone" + (over ? " is-over" : "")}
            onClick={() => fileRef.current && fileRef.current.click()}
            onDragOver={e => { e.preventDefault(); setOver(true); }}
            onDragLeave={() => setOver(false)}
            onDrop={e => { e.preventDefault(); setOver(false); if (e.dataTransfer.files.length) handleFiles(e.dataTransfer.files); }}>
            Glissez un fichier ici, ou touchez pour parcourir
          </div>
        </div>
      </div>
    );
  }

  window.SessionDetail = SessionDetail;
})();
