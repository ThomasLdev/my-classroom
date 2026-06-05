/* Calendar.jsx — le cœur : vue jour (3 variantes + swipe), bande semaine, grille semaine desktop. */
(function () {
  const { useState, useRef, useEffect } = React;
  const D = window.DATA, L = window.LOGIC, Icon = window.Icon;
  const { tintOf, accentOf, deepOf, timeRange } = window.UI;

  function useMediaQuery(q) {
    const [m, setM] = useState(() => window.matchMedia(q).matches);
    useEffect(() => {
      const mq = window.matchMedia(q);
      const fn = () => setM(mq.matches);
      mq.addEventListener("change", fn);
      return () => mq.removeEventListener("change", fn);
    }, [q]);
    return m;
  }
  window.useMediaQuery = useMediaQuery;

  /* ---------- pastille classe ---------- */
  function Chip({ cls }) {
    return (
      <span className="classchip" style={{ background: tintOf(cls.color), color: deepOf(cls.color) }}>
        <i style={{ background: accentOf(cls.color) }} />{cls.name}
      </span>
    );
  }

  /* ---------- carte de séance ---------- */
  function SessionCard({ session, store, onOpen }) {
    const cls = D.classById(session.classId);
    const vm = L.sessionView(session, store);
    const pct = vm.total ? Math.round((vm.done / vm.total) * 100) : 0;
    const preview = vm.own.slice(0, 3);
    return (
      <button className="session-card" style={{ "--c-accent": accentOf(cls.color) }} onClick={() => onOpen(session)}>
        <span className="session-card__bar" style={{ background: accentOf(cls.color) }} />
        <div className="session-card__top">
          <Chip cls={cls} />
          <span className="session-card__time tnum">{timeRange(session.start, session.end)}</span>
        </div>
        <div className="session-card__room">
          <Icon name="pin" /> Salle {cls.room} · Français
        </div>

        {(preview.length > 0) && (
          <div className="act-mini">
            {preview.map(a => (
              <div key={a.id} className={"act-mini__row" + (a.done ? " done" : "")}>
                <span className="tick">{a.done && <Icon name="check" strokeWidth={2.4} />}</span>
                <span className="label">{a.title}</span>
              </div>
            ))}
            {vm.own.length > 3 && <div className="act-mini__more">+ {vm.own.length - 3} autre(s) activité(s)</div>}
          </div>
        )}

        {(vm.total > 0 || vm.carried.length > 0) && (
          <div className="session-card__progress">
            {vm.total > 0 && <>
              <div className="progressbar"><i style={{ width: pct + "%", background: accentOf(cls.color) }} /></div>
              <span className="tnum">{vm.done}/{vm.total}</span>
            </>}
            {vm.carried.length > 0 && (
              <span className="carry-flag"><Icon name="rotate" /> {vm.carried.length} reportée{vm.carried.length > 1 ? "s" : ""}</span>
            )}
          </div>
        )}
        {(vm.total === 0 && vm.carried.length === 0) && (
          <div className="session-card__progress"><span style={{ color: "var(--ink-3)" }}>Aucune activité prévue — touchez pour planifier</span></div>
        )}
      </button>
    );
  }

  /* ---------- ligne ruban (compact) ---------- */
  function RibbonRow({ session, store, onOpen }) {
    const cls = D.classById(session.classId);
    const vm = L.sessionView(session, store);
    return (
      <button className="ribbon-row" style={{ borderLeftColor: accentOf(cls.color), background: tintOf(cls.color) }} onClick={() => onOpen(session)}>
        <div className="ribbon-row__time tnum">{session.start}<small>{session.end}</small></div>
        <div className="ribbon-row__body">
          <div className="ribbon-row__name" style={{ color: deepOf(cls.color) }}>{cls.name} · Français</div>
          <div className="ribbon-row__meta">
            Salle {cls.room}
            {vm.total > 0 && <> · {vm.done}/{vm.total} activités</>}
            {vm.carried.length > 0 && <> · {vm.carried.length} reportée{vm.carried.length > 1 ? "s" : ""}</>}
          </div>
        </div>
        <span className="ribbon-row__chev"><Icon name="chevron-right" /></span>
      </button>
    );
  }

  /* ---------- carte évènement ---------- */
  function EventCard({ event, onOpen }) {
    return (
      <button className="event-card" onClick={() => onOpen(event)}>
        <span className="event-card__ic"><Icon name="bell" /></span>
        <div className="event-card__body">
          <div className="event-card__title">{event.title}</div>
          <div className="event-card__meta">{event.desc}</div>
        </div>
        <span className="event-card__time tnum">{event.start}</span>
      </button>
    );
  }

  /* ---------- en-tête de jour ---------- */
  function DayHeader({ date, onPrev, onNext }) {
    const isToday = D.isSameDay(date, D.today);
    return (
      <div className="dayhead">
        <div className="dayhead__date">
          <div className="dayhead__dow">{D.DOW[date.getDay()]}</div>
          <div className="dayhead__sub tnum">{date.getDate()} {D.MONTHS[date.getMonth()]} {date.getFullYear()}</div>
        </div>
        {isToday && <span className="dayhead__today">Aujourd'hui</span>}
        <div className="dayhead__nav">
          <button className="iconbtn" onClick={onPrev} aria-label="Jour précédent"><Icon name="chevron-left" /></button>
          <button className="iconbtn" onClick={onNext} aria-label="Jour suivant"><Icon name="chevron-right" /></button>
        </div>
      </div>
    );
  }

  /* ---------- bande semaine cliquable ---------- */
  function WeekStrip({ selected, events, onPick }) {
    const monday = D.startOfWeek(selected);
    const days = Array.from({ length: 7 }, (_, i) => D.addDays(monday, i));
    return (
      <div className="weekstrip">
        {days.map((d, i) => {
          const ds = D.ymd(d);
          const dots = L.dayDots(ds, events);
          const sel = D.isSameDay(d, selected);
          const today = D.isSameDay(d, D.today);
          const weekend = i >= 5;
          return (
            <button key={ds} className={"weekstrip__cell" + (sel ? " is-selected" : "") + (today ? " is-today" : "") + (weekend ? " is-weekend" : "")} onClick={() => onPick(d)}>
              <span className="weekstrip__dow">{D.DOW_SHORT[d.getDay()]}</span>
              <span className="weekstrip__num tnum">{d.getDate()}</span>
              <span className="weekstrip__dots">
                {dots.colors.slice(0, 4).map((c, k) => <i key={k} style={{ background: accentOf(c) }} />)}
                {dots.events > 0 && <i style={{ background: "var(--event-accent)" }} />}
              </span>
            </button>
          );
        })}
      </div>
    );
  }

  /* ---------- vue jour (avec swipe + variantes) ---------- */
  function DayView({ selected, setSelected, store, events, variant, onOpenSession, onOpenEvent }) {
    const [dx, setDx] = useState(0);
    const dragging = useRef(false), horiz = useRef(false);
    const startX = useRef(0), startY = useRef(0);
    const dirRef = useRef(0);

    function navigate(newDate) {
      dirRef.current = D.ymd(newDate) > D.ymd(selected) ? 1 : (D.ymd(newDate) < D.ymd(selected) ? -1 : 0);
      setSelected(newDate);
      setDx(0);
    }
    const go = (delta) => navigate(D.addDays(selected, delta));

    function onStart(e) {
      const t = e.touches ? e.touches[0] : e;
      startX.current = t.clientX; startY.current = t.clientY;
      dragging.current = true; horiz.current = false;
    }
    function onMove(e) {
      if (!dragging.current) return;
      const t = e.touches ? e.touches[0] : e;
      const ddx = t.clientX - startX.current, ddy = t.clientY - startY.current;
      if (!horiz.current) {
        if (Math.abs(ddx) > 10 && Math.abs(ddx) > Math.abs(ddy)) horiz.current = true;
        else if (Math.abs(ddy) > 12) { dragging.current = false; return; }
      }
      if (horiz.current) setDx(ddx);
    }
    function onEnd() {
      if (!dragging.current) return;
      dragging.current = false;
      if (dx > 64) go(-1);
      else if (dx < -64) go(1);
      else setDx(0);
    }

    const ds = D.ymd(selected);
    const items = L.dayItems(ds, events);
    const enterAttr = dirRef.current === 1 ? "next" : dirRef.current === -1 ? "prev" : undefined;
    const resist = dx === 0 ? 0 : Math.sign(dx) * Math.min(Math.abs(dx) * 0.55, 130);

    return (
      <div className="dayview"
        onTouchStart={onStart} onTouchMove={onMove} onTouchEnd={onEnd}>
        <div className="dayview__inner" key={ds} data-enter={enterAttr}
          style={dragging.current || dx !== 0 ? { transform: `translateX(${resist}px)`, opacity: 1 - Math.min(Math.abs(dx) / 600, 0.25), transition: "none" } : null}>
          {items.length === 0 ? (
            <div className="empty">
              <Icon name="calendar" />
              <p>Pas de cours ni d'évènement ce jour-là.</p>
            </div>
          ) : (
            <DayBody items={items} store={store} variant={variant} onOpenSession={onOpenSession} onOpenEvent={onOpenEvent} />
          )}
        </div>
      </div>
    );
  }

  function DayBody({ items, store, variant, onOpenSession, onOpenEvent }) {
    if (variant === "timeline") {
      return (
        <div className="timeline stagger">
          {items.map(it => (
            <div className="tl-row" key={it.id}>
              <div className="tl-time tnum">{it.start}</div>
              <div className="tl-track">
                {it.kind === "session"
                  ? <SessionCard session={it} store={store} onOpen={onOpenSession} />
                  : <EventCard event={it} onOpen={onOpenEvent} />}
              </div>
            </div>
          ))}
        </div>
      );
    }
    if (variant === "ribbon") {
      return (
        <div className="ribbon stagger">
          {items.map(it => it.kind === "session"
            ? <RibbonRow key={it.id} session={it} store={store} onOpen={onOpenSession} />
            : <EventCard key={it.id} event={it} onOpen={onOpenEvent} />)}
        </div>
      );
    }
    // cards (défaut)
    return (
      <div className="stagger" style={{ display: "flex", flexDirection: "column", gap: 12, marginTop: 10 }}>
        {items.map(it => it.kind === "session"
          ? <SessionCard key={it.id} session={it} store={store} onOpen={onOpenSession} />
          : <EventCard key={it.id} event={it} onOpen={onOpenEvent} />)}
      </div>
    );
  }

  /* ---------- grille semaine (desktop) ---------- */
  const WG_START = 8, WG_END = 18, WG_HOUR = 64;
  function WeekGrid({ selected, store, events, onOpenSession, onOpenEvent }) {
    const monday = D.startOfWeek(selected);
    const days = Array.from({ length: 5 }, (_, i) => D.addDays(monday, i));
    const hours = Array.from({ length: WG_END - WG_START }, (_, i) => WG_START + i);
    const yOf = (hhmm) => (D.minutesOf(hhmm) - WG_START * 60) / 60 * WG_HOUR;

    return (
      <div className="weekgrid">
        <div className="wg-corner" />
        {days.map(d => {
          const today = D.isSameDay(d, D.today);
          return (
            <div className={"wg-colhead" + (today ? " is-today" : "")} key={D.ymd(d)}>
              <div className="d">{D.DOW_SHORT[d.getDay()]}</div>
              <div className="n tnum">{d.getDate()}</div>
            </div>
          );
        })}

        <div className="wg-timecol">
          {hours.map(h => <div className="wg-hour" key={h}><span className="tnum">{String(h).padStart(2, "0")}:00</span></div>)}
        </div>

        {days.map(d => {
          const ds = D.ymd(d);
          const sessions = D.sessionsForDate(ds);
          const evs = (events || []).filter(e => e.date === ds && D.minutesOf(e.start) >= WG_START * 60 && D.minutesOf(e.start) < WG_END * 60);
          return (
            <div className="wg-col" key={ds}>
              {hours.map(h => <div className="wg-hourline" key={h} />)}
              {sessions.map(s => {
                const cls = D.classById(s.classId);
                const vm = L.sessionView({ ...s, id: D.sessionId(s.slotId, ds) }, store);
                const top = yOf(s.start), h = Math.max((D.minutesOf(s.end) - D.minutesOf(s.start)) / 60 * WG_HOUR, 40);
                return (
                  <div key={s.id} className="wg-event" style={{ top, height: h - 4, background: tintOf(cls.color), borderColor: accentOf(cls.color) }}
                    onClick={() => onOpenSession({ ...s, id: D.sessionId(s.slotId, ds) })}>
                    <div className="wn" style={{ color: deepOf(cls.color) }}>{cls.name}</div>
                    <div className="wt tnum">{s.start}–{s.end}</div>
                    {(vm.total > 0 || vm.carried.length > 0) && (
                      <div className="wc" style={{ color: deepOf(cls.color) }}>
                        {vm.total > 0 && <span>{vm.done}/{vm.total}</span>}
                        {vm.carried.length > 0 && <span>· ↻{vm.carried.length}</span>}
                      </div>
                    )}
                  </div>
                );
              })}
              {evs.map(e => {
                const top = yOf(e.start);
                return (
                  <div key={e.id} className="wg-event wg-event--evt" style={{ top, height: 40 }} onClick={() => onOpenEvent(e)}>
                    <div className="wn">{e.title}</div>
                    <div className="wt tnum">{e.start}</div>
                  </div>
                );
              })}
            </div>
          );
        })}
      </div>
    );
  }

  window.CAL = { DayView, WeekStrip, WeekGrid, DayHeader };
})();
